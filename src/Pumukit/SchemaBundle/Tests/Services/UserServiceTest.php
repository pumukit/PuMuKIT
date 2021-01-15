<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\EventListener\PermissionProfileListener;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\CreateUserService;
use Pumukit\SchemaBundle\Services\MultimediaObjectEventDispatcherService;
use Pumukit\SchemaBundle\Services\PermissionProfileEventDispatcherService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PermissionService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UpdateUserService;
use Pumukit\SchemaBundle\Services\UserEventDispatcherService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

/**
 * @internal
 * @coversNothing
 */
class UserServiceTest extends PumukitTestCase
{
    private $repo;
    private $updateUserService;
    private $createUserService;
    private $userPasswordEncoder;
    private $permissionProfileRepo;
    private $userService;
    private $logger;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->repo = $this->dm->getRepository(User::class);
        $this->permissionProfileRepo = $this->dm->getRepository(PermissionProfile::class);

        $dispatcher = new EventDispatcher();
        $userDispatcher = new UserEventDispatcherService($dispatcher);
        $permissionProfileDispatcher = new PermissionProfileEventDispatcherService($dispatcher);
        $multimediaObjectEventDispatcher = new MultimediaObjectEventDispatcherService($dispatcher);
        $permissionService = new PermissionService($this->dm);
        $permissionProfileService = new PermissionProfileService(
            $this->dm,
            $permissionProfileDispatcher,
            $permissionService
        );

        $tokenStorage = new TokenStorage();
        $personalScopeDeleteOwners = false;
        $sendEmailWhenAddUserOwner = false;

        $this->userService = new UserService(
            $this->dm,
            $userDispatcher,
            $permissionService,
            $permissionProfileService,
            $multimediaObjectEventDispatcher,
            $tokenStorage,
            $personalScopeDeleteOwners,
            $sendEmailWhenAddUserOwner
        );

        $this->userPasswordEncoder = $this->getMockBuilder(UserPasswordEncoder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $personService = $this->getMockBuilder(PersonService::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->createUserService = new CreateUserService(
            $this->dm,
            $this->userPasswordEncoder,
            $permissionProfileService,
            $personService,
            $userDispatcher
        );

        $this->updateUserService = new UpdateUserService(
            $this->dm,
            $permissionProfileService,
            $this->userPasswordEncoder,
            $userDispatcher
        );

        $listener = new PermissionProfileListener($this->dm, $this->userService, $this->updateUserService);
        $dispatcher->addListener('permissionprofile.update', [$listener, 'postUpdate']);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->permissionProfileRepo = null;
        $this->userService = null;
        gc_collect_cycles();
    }

    public function testAddAndRemoveRoles()
    {
        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@mail.com');

        $this->dm->persist($user);
        $this->dm->flush();

        $permissions1 = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_ROLES];
        $user = $this->userService->addRoles($user, $permissions1);

        $user = $this->repo->find($user->getId());

        static::assertTrue($user->hasRole(Permission::ACCESS_DASHBOARD));
        static::assertTrue($user->hasRole(Permission::ACCESS_ROLES));
        static::assertFalse($user->hasRole(Permission::ACCESS_TAGS));

        $permissions2 = [Permission::ACCESS_TAGS, Permission::ACCESS_ROLES];
        $user = $this->userService->removeRoles($user, $permissions2);

        $user = $this->repo->find($user->getId());

        static::assertTrue($user->hasRole(Permission::ACCESS_DASHBOARD));
        static::assertFalse($user->hasRole(Permission::ACCESS_ROLES));
        static::assertFalse($user->hasRole(Permission::ACCESS_TAGS));
    }

    public function testCountAndGetUsersWithPermissionProfile()
    {
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('permissionprofile1');
        $permissionProfile1->setDefault(true);
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('permissionprofile2');
        $this->dm->persist($permissionProfile2);
        $this->dm->flush();

        $user1 = $this->createUserService->createUser('test1', 'passwordExample', 'test1@mail.com', 'User name', $permissionProfile1);
        $user2 = $this->createUserService->createUser('test2', 'passwordExample', 'test2@mail.com', 'User name', $permissionProfile2);
        $user3 = $this->createUserService->createUser('test3', 'passwordExample', 'test3@mail.com', 'User name', $permissionProfile1);

        static::assertEquals(2, $this->userService->countUsersWithPermissionProfile($permissionProfile1));
        static::assertEquals(1, $this->userService->countUsersWithPermissionProfile($permissionProfile2));

        $usersProfile1 = $this->userService->getUsersWithPermissionProfile($permissionProfile1)->toArray();
        static::assertContains($user1, $usersProfile1);
        static::assertNotContains($user2, $usersProfile1);
        static::assertContains($user3, $usersProfile1);

        $usersProfile2 = $this->userService->getUsersWithPermissionProfile($permissionProfile2)->toArray();
        static::assertNotContains($user1, $usersProfile2);
        static::assertContains($user2, $usersProfile2);
        static::assertNotContains($user3, $usersProfile2);
    }

    public function testGetUserPermissions()
    {
        $permissions = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_TAGS];
        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');
        $permissionProfile->setPermissions($permissions);
        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@mail.com');
        $user->setPermissionProfile($permissionProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        $user = $this->userService->addRoles($user, $permissionProfile->getPermissions());

        static::assertNotEquals($permissions, $user->getRoles());
        static::assertEquals($permissions, $this->userService->getUserPermissions($user->getRoles()));
    }

    public function testAddUserScope()
    {
        $notValidScope = 'NOT_VALID_SCOPE';

        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@mail.com');
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertNotContains(PermissionProfile::SCOPE_GLOBAL, $user->getRoles());
        static::assertNotContains(PermissionProfile::SCOPE_PERSONAL, $user->getRoles());
        static::assertNotContains(PermissionProfile::SCOPE_NONE, $user->getRoles());

        $user = $this->userService->addUserScope($user, PermissionProfile::SCOPE_PERSONAL);

        static::assertNotContains(PermissionProfile::SCOPE_GLOBAL, $user->getRoles());
        static::assertContains(PermissionProfile::SCOPE_PERSONAL, $user->getRoles());
        static::assertNotContains(PermissionProfile::SCOPE_NONE, $user->getRoles());
        static::assertNotContains($notValidScope, $user->getRoles());

        $user = $this->userService->addUserScope($user, $notValidScope);

        static::assertNotContains(PermissionProfile::SCOPE_GLOBAL, $user->getRoles());
        static::assertContains(PermissionProfile::SCOPE_PERSONAL, $user->getRoles());
        static::assertNotContains(PermissionProfile::SCOPE_NONE, $user->getRoles());
        static::assertNotContains($notValidScope, $user->getRoles());
    }

    public function testGetUserScope()
    {
        $permissions = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_TAGS];
        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');
        $permissionProfile->setPermissions($permissions);
        $permissionProfile->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@mail.com');
        $user->setPermissionProfile($permissionProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        $userScope = $this->userService->getUserScope($user->getRoles());

        static::assertNotEquals(PermissionProfile::SCOPE_GLOBAL, $userScope);
        static::assertNotEquals(PermissionProfile::SCOPE_PERSONAL, $userScope);
        static::assertNotEquals(PermissionProfile::SCOPE_NONE, $userScope);
        static::assertCount(1, $user->getRoles());

        $user = $this->userService->addRoles($user, $permissionProfile->getPermissions());
        static::assertCount(3, $user->getRoles());
        $user = $this->userService->addUserScope($user, PermissionProfile::SCOPE_PERSONAL);
        static::assertCount(4, $user->getRoles());

        $userScope = $this->userService->getUserScope($user->getRoles());

        static::assertNotEquals(PermissionProfile::SCOPE_GLOBAL, $userScope);
        static::assertEquals(PermissionProfile::SCOPE_PERSONAL, $userScope);
        static::assertNotEquals(PermissionProfile::SCOPE_NONE, $userScope);
    }

    public function testSetUserScope()
    {
        $permissions = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_TAGS];
        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');
        $permissionProfile->setPermissions($permissions);
        $permissionProfile->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@mail.com');
        $user->setPermissionProfile($permissionProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertCount(1, $user->getRoles());
        $user = $this->userService->addRoles($user, $permissionProfile->getPermissions());
        static::assertCount(3, $user->getRoles());
        $user = $this->userService->addUserScope($user, PermissionProfile::SCOPE_PERSONAL);
        static::assertCount(4, $user->getRoles());

        $userScope = $this->userService->getUserScope($user->getRoles());

        static::assertNotEquals(PermissionProfile::SCOPE_GLOBAL, $userScope);
        static::assertEquals(PermissionProfile::SCOPE_PERSONAL, $userScope);
        static::assertNotEquals(PermissionProfile::SCOPE_NONE, $userScope);

        $user = $this->userService->setUserScope($user, $userScope, PermissionProfile::SCOPE_GLOBAL);

        $newUserScope = $this->userService->getUserScope($user->getRoles());

        static::assertEquals(PermissionProfile::SCOPE_GLOBAL, $newUserScope);
        static::assertNotEquals(PermissionProfile::SCOPE_PERSONAL, $newUserScope);
        static::assertNotEquals(PermissionProfile::SCOPE_NONE, $newUserScope);
        static::assertCount(4, $user->getRoles());
    }

    public function testDelete()
    {
        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');
        $permissionProfile->setScope(PermissionProfile::SCOPE_PERSONAL);
        $permissionProfile->setDefault(true);
        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $user = $this->createUserService->createUser('test', 'passwordExample', 'test@mail.com', 'User name', $permissionProfile);

        static::assertCount(1, $this->repo->findAll());

        $this->userService->delete($user);

        static::assertCount(0, $this->repo->findAll());
    }

    public function testHasScopes()
    {
        $globalProfile = new PermissionProfile();
        $globalProfile->setName('global');
        $globalProfile->setScope(PermissionProfile::SCOPE_GLOBAL);

        $personalProfile = new PermissionProfile();
        $personalProfile->setName('personal');
        $personalProfile->setScope(PermissionProfile::SCOPE_PERSONAL);

        $noneProfile = new PermissionProfile();
        $noneProfile->setName('none');
        $noneProfile->setScope(PermissionProfile::SCOPE_NONE);

        $this->dm->persist($globalProfile);
        $this->dm->persist($personalProfile);
        $this->dm->persist($noneProfile);
        $this->dm->flush();

        $user = new User();
        $user->setUsername('test');
        $user->setPassword('pass');
        $user->setPermissionProfile($globalProfile);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertTrue($this->userService->hasGlobalScope($user));
        static::assertFalse($this->userService->hasPersonalScope($user));
        static::assertFalse($this->userService->hasNoneScope($user));

        $user->setPermissionProfile($personalProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertFalse($this->userService->hasGlobalScope($user));
        static::assertTrue($this->userService->hasPersonalScope($user));
        static::assertFalse($this->userService->hasNoneScope($user));

        $user->setPermissionProfile($noneProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertFalse($this->userService->hasGlobalScope($user));
        static::assertFalse($this->userService->hasPersonalScope($user));
        static::assertTrue($this->userService->hasNoneScope($user));
    }

    public function testAddGroup()
    {
        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('name1');

        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('name2');

        $group3 = new Group();
        $group3->setKey('key3');
        $group3->setName('name3');

        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@mail.com');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertCount(0, $user->getGroups());
        static::assertFalse($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));

        $this->userService->addGroup($group1, $user);

        static::assertCount(1, $user->getGroups());
        static::assertTrue($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));

        $this->userService->addGroup($group1, $user);

        static::assertCount(1, $user->getGroups());
        static::assertTrue($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));
        static::assertFalse($user->containsGroup($group3));

        $this->userService->addGroup($group2, $user);

        static::assertCount(2, $user->getGroups());
        static::assertTrue($user->containsGroup($group1));
        static::assertTrue($user->containsGroup($group2));
        static::assertFalse($user->containsGroup($group3));

        $this->userService->addGroup($group3, $user);

        static::assertCount(3, $user->getGroups());
        static::assertTrue($user->containsGroup($group1));
        static::assertTrue($user->containsGroup($group2));
        static::assertTrue($user->containsGroup($group3));
    }

    public function testDeleteGroup()
    {
        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('name1');

        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('name2');

        $group3 = new Group();
        $group3->setKey('key3');
        $group3->setName('name3');

        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@mail.com');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertCount(0, $user->getGroups());
        static::assertFalse($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));

        $this->userService->addGroup($group1, $user);

        $user = $this->repo->find($user->getId());

        static::assertCount(1, $user->getGroups());
        static::assertTrue($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));

        $this->userService->deleteGroup($group1, $user);

        $user = $this->repo->find($user->getId());

        static::assertCount(0, $user->getGroups());
        static::assertFalse($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));
        static::assertFalse($user->containsGroup($group3));

        $this->userService->deleteGroup($group2, $user);

        $user = $this->repo->find($user->getId());

        static::assertCount(0, $user->getGroups());
        static::assertFalse($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));
        static::assertFalse($user->containsGroup($group3));

        $this->userService->addGroup($group3, $user);

        $user = $this->repo->find($user->getId());

        static::assertCount(1, $user->getGroups());
        static::assertFalse($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));
        static::assertTrue($user->containsGroup($group3));

        $this->userService->deleteGroup($group3, $user);

        $user = $this->repo->find($user->getId());

        static::assertCount(0, $user->getGroups());
        static::assertFalse($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));
        static::assertFalse($user->containsGroup($group3));
    }

    public function testIsAllowedToModifyUserGroup()
    {
        $localGroup = new Group();
        $localGroup->setKey('local_key');
        $localGroup->setName('Local Group');
        $localGroup->setOrigin(Group::ORIGIN_LOCAL);

        $casGroup = new Group();
        $casGroup->setKey('cas_key');
        $casGroup->setName('CAS Group');
        $casGroup->setOrigin('cas');

        $localUser = new User();
        $localUser->setUsername('local_user');
        $localUser->setEmail('local_user@mail.com');
        $localUser->setOrigin(User::ORIGIN_LOCAL);

        $casUser = new User();
        $casUser->setUsername('cas_user');
        $casUser->setEmail('cas_user@mail.com');
        $casUser->setOrigin('cas');

        $this->dm->persist($localGroup);
        $this->dm->persist($casGroup);
        $this->dm->persist($localUser);
        $this->dm->persist($casUser);
        $this->dm->flush();

        static::assertTrue($this->userService->isAllowedToModifyUserGroup($localUser, $localGroup));
        static::assertFalse($this->userService->isAllowedToModifyUserGroup($casUser, $casGroup));
        static::assertTrue($this->userService->isAllowedToModifyUserGroup($localUser, $casGroup));
        static::assertTrue($this->userService->isAllowedToModifyUserGroup($casUser, $localGroup));
    }

    public function testAddGroupLocalCas()
    {
        $localGroup = new Group();
        $localGroup->setKey('local_key');
        $localGroup->setName('Local Group');
        $localGroup->setOrigin(Group::ORIGIN_LOCAL);

        $casUser = new User();
        $casUser->setUsername('cas_user');
        $casUser->setEmail('cas_user@mail.com');
        $casUser->setOrigin('cas');

        $this->dm->persist($localGroup);
        $this->dm->persist($casUser);
        $this->dm->flush();

        $this->userService->addGroup($localGroup, $casUser);

        static::assertTrue($casUser->containsGroup($localGroup));
    }

    public function testAddGroupCasLocal()
    {
        $casGroup = new Group();
        $casGroup->setKey('cas_key');
        $casGroup->setName('CAS Group');
        $casGroup->setOrigin('cas');

        $localUser = new User();
        $localUser->setUsername('local_user');
        $localUser->setEmail('local_user@mail.com');
        $localUser->setOrigin(User::ORIGIN_LOCAL);

        $this->dm->persist($casGroup);
        $this->dm->persist($localUser);
        $this->dm->flush();

        $this->userService->addGroup($casGroup, $localUser);

        static::assertTrue($localUser->containsGroup($casGroup));
    }

    public function testExceptionAddGroupCasCas()
    {
        $this->expectExceptionMessage('Not allowed to add group');
        $this->expectException(\Exception::class);

        $casGroup = new Group();
        $casGroup->setKey('cas_key');
        $casGroup->setName('CAS Group');
        $casGroup->setOrigin('cas');

        $casUser = new User();
        $casUser->setUsername('cas_user');
        $casUser->setEmail('cas_user@mail.com');
        $casUser->setOrigin('cas');

        $this->dm->persist($casGroup);
        $this->dm->persist($casUser);
        $this->dm->flush();

        $this->userService->addGroup($casGroup, $casUser);
    }

    public function testDeleteGroupLocalCas()
    {
        $localGroup = new Group();
        $localGroup->setKey('local_key');
        $localGroup->setName('Local Group');
        $localGroup->setOrigin(Group::ORIGIN_LOCAL);

        $casUser = new User();
        $casUser->setUsername('cas_user');
        $casUser->setEmail('cas_user@mail.com');
        $casUser->setOrigin('cas');

        $this->dm->persist($localGroup);
        $this->dm->persist($casUser);
        $this->dm->flush();

        $casUser->addGroup($localGroup);
        $this->dm->persist($casUser);
        $this->dm->flush();

        static::assertTrue($casUser->containsGroup($localGroup));

        $this->userService->deleteGroup($localGroup, $casUser);

        static::assertFalse($casUser->containsGroup($localGroup));
    }

    public function testDeleteGroupCasLocal()
    {
        $casGroup = new Group();
        $casGroup->setKey('cas_key');
        $casGroup->setName('CAS Group');
        $casGroup->setOrigin('cas');

        $localUser = new User();
        $localUser->setUsername('local_user');
        $localUser->setEmail('local_user@mail.com');
        $localUser->setOrigin(User::ORIGIN_LOCAL);

        $this->dm->persist($casGroup);
        $this->dm->persist($localUser);
        $this->dm->flush();

        $localUser->addGroup($casGroup);
        $this->dm->persist($localUser);
        $this->dm->flush();

        static::assertTrue($localUser->containsGroup($casGroup));

        $this->userService->deleteGroup($casGroup, $localUser);

        static::assertFalse($localUser->containsGroup($casGroup));
    }

    public function testExceptionDeleteGroupCasCas()
    {
        $this->expectExceptionMessage('Not allowed to delete group');
        $this->expectException(\Exception::class);

        $casGroup = new Group();
        $casGroup->setKey('cas_key');
        $casGroup->setName('CAS Group');
        $casGroup->setOrigin('cas');

        $casUser = new User();
        $casUser->setUsername('cas_user');
        $casUser->setEmail('cas_user@mail.com');
        $casUser->setOrigin('cas');

        $this->dm->persist($casGroup);
        $this->dm->persist($casUser);
        $this->dm->flush();

        $casUser->addGroup($casGroup);
        $this->dm->persist($casUser);
        $this->dm->flush();

        $this->userService->deleteGroup($casGroup, $casUser);
    }

    public function testFindWithGroup()
    {
        $localGroup = new Group();
        $localGroup->setKey('local_key');
        $localGroup->setName('Local Group');
        $localGroup->setOrigin(Group::ORIGIN_LOCAL);

        $casGroup = new Group();
        $casGroup->setKey('cas_key');
        $casGroup->setName('CAS Group');
        $casGroup->setOrigin('cas');

        $localUser = new User();
        $localUser->setUsername('local_user');
        $localUser->setEmail('local_user@mail.com');
        $localUser->setOrigin(User::ORIGIN_LOCAL);
        $localUser->addGroup($localGroup);

        $casUser = new User();
        $casUser->setUsername('cas_user');
        $casUser->setEmail('cas_user@mail.com');
        $casUser->setOrigin('cas');
        $casUser->addGroup($casGroup);

        $this->dm->persist($localGroup);
        $this->dm->persist($casGroup);
        $this->dm->persist($localUser);
        $this->dm->persist($casUser);
        $this->dm->flush();

        $usersLocalGroup = $this->userService->findWithGroup($localGroup)->toArray();
        $usersCasGroup = $this->userService->findWithGroup($casGroup)->toArray();
        static::assertContains($localUser, $usersLocalGroup);
        static::assertNotContains($casUser, $usersLocalGroup);
        static::assertNotContains($localUser, $usersCasGroup);
        static::assertContains($casUser, $usersCasGroup);
    }

    public function testDeleteAllFromGroup()
    {
        $group = new Group();
        $group->setKey('key');
        $group->setName('group');
        $this->dm->persist($group);
        $this->dm->flush();

        static::assertCount(0, $this->userService->findWithGroup($group)->toArray());

        $user1 = new User();
        $user1->setUsername('user1');
        $user1->setEmail('user1@mail.com');
        $user1->addGroup($group);

        $user2 = new User();
        $user2->setUsername('user2');
        $user2->setEmail('user2@mail.com');
        $user2->addGroup($group);

        $user3 = new User();
        $user3->setUsername('user3');
        $user3->setEmail('user3@mail.com');
        $user3->addGroup($group);

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->flush();

        static::assertCount(3, $this->userService->findWithGroup($group)->toArray());

        $this->userService->deleteAllFromGroup($group);
        static::assertCount(0, $this->userService->findWithGroup($group)->toArray());
    }

    public function testIsUserLastRelation()
    {
        $permissions1 = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_ROLES];
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setPermissions($permissions1);
        $permissionProfile1->setName('permissionprofile1');
        $permissionProfile1->setScope(PermissionProfile::SCOPE_GLOBAL);
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        $user = new User();
        $user->setUsername('user');
        $user->setEmail('user@mail.com');
        $user->setPermissionProfile($permissionProfile1);

        $person1 = new Person();
        $person1->setEmail('person1@mail.com');
        $person2 = new Person();
        $person2->setEmail('person2@mail.com');
        $person3 = new Person();
        $person3->setEmail('person3@mail.com');

        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('group1');
        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('group2');

        $this->dm->persist($user);
        $this->dm->persist($person1);
        $this->dm->persist($person2);
        $this->dm->persist($person3);
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();

        $user->setPerson($person2);
        $person2->setUser($user);
        $this->dm->persist($user);
        $this->dm->persist($person2);
        $this->dm->flush();

        $aux = 'first_second_';
        $owners1 = [$aux.$person2->getId(), $aux.$person3->getId()];
        $owners2 = [$aux.$person1->getId(), $aux.$person2->getId(), $aux.$person3->getId()];
        $groups = [$aux.$group1->getId(), $aux.$group2->getId()];

        static::assertFalse($this->userService->isUserLastRelation($user, null, $person1->getId(), $owners1, $groups));
        static::assertFalse($this->userService->isUserLastRelation($user, null, $person2->getId(), $owners1, $groups));
        static::assertFalse($this->userService->isUserLastRelation($user, null, $person3->getId(), $owners1, $groups));

        $permissionProfile1->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        static::assertFalse($this->userService->isUserLastRelation($user, null, $person1->getId(), $owners1, $groups));
        static::assertTrue($this->userService->isUserLastRelation($user, null, $person2->getId(), $owners1, $groups));
        static::assertFalse($this->userService->isUserLastRelation($user, null, $person3->getId(), $owners1, $groups));

        $user->addGroup($group2);
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertFalse($this->userService->isUserLastRelation($user, null, $person1->getId(), $owners1, $groups));
        static::assertFalse($this->userService->isUserLastRelation($user, null, $person2->getId(), $owners1, $groups));
        static::assertFalse($this->userService->isUserLastRelation($user, null, $person3->getId(), $owners1, $groups));

        static::assertTrue($this->userService->isUserLastRelation($user, null, $person1->getId(), [], []));
        static::assertTrue($this->userService->isUserLastRelation($user, null, $person2->getId(), [], []));
        static::assertTrue($this->userService->isUserLastRelation($user, null, $person3->getId(), [], []));
    }

    public function testIsLoggedPersonToRemoveFromOwner()
    {
        $permissions1 = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_ROLES];
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setPermissions($permissions1);
        $permissionProfile1->setName('permissionprofile1');
        $permissionProfile1->setScope(PermissionProfile::SCOPE_GLOBAL);
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        $user = new User();
        $user->setUsername('user');
        $user->setEmail('user@mail.com');
        $user->setPermissionProfile($permissionProfile1);

        $person1 = new Person();
        $person1->setEmail('person1@mail.com');

        $person2 = new Person();
        $person2->setEmail('person2@mail.com');

        $person3 = new Person();
        $person3->setEmail('person3@mail.com');

        $this->dm->persist($user);
        $this->dm->persist($person1);
        $this->dm->persist($person2);
        $this->dm->persist($person3);
        $this->dm->flush();

        $user->setPerson($person2);
        $person2->setUser($user);
        $this->dm->persist($user);
        $this->dm->persist($person2);
        $this->dm->flush();

        static::assertFalse($this->userService->isLoggedPersonToRemoveFromOwner($user, $person1->getId()));
        static::assertFalse($this->userService->isLoggedPersonToRemoveFromOwner($user, $person2->getId()));
        static::assertFalse($this->userService->isLoggedPersonToRemoveFromOwner($user, $person3->getId()));

        $permissionProfile1->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        static::assertFalse($this->userService->isLoggedPersonToRemoveFromOwner($user, $person1->getId()));
        static::assertTrue($this->userService->isLoggedPersonToRemoveFromOwner($user, $person2->getId()));
        static::assertFalse($this->userService->isLoggedPersonToRemoveFromOwner($user, $person3->getId()));
    }

    public function testIsUserInOwners()
    {
        $user = new User();
        $user->setUsername('user');
        $user->setEmail('user@mail.com');

        $person1 = new Person();
        $person1->setEmail('person1@mail.com');

        $person2 = new Person();
        $person2->setEmail('person2@mail.com');

        $person3 = new Person();
        $person3->setEmail('person3@mail.com');

        $this->dm->persist($user);
        $this->dm->persist($person1);
        $this->dm->persist($person2);
        $this->dm->persist($person3);
        $this->dm->flush();

        $user->setPerson($person1);
        $person1->setUser($user);
        $this->dm->persist($user);
        $this->dm->persist($person1);
        $this->dm->flush();

        $aux = 'first_second_';

        $owners1 = [$aux.$person2->getId(), $aux.$person3->getId()];
        static::assertFalse($this->userService->isUserInOwners($user, $owners1));

        $owners2 = [$aux.$person1->getId(), $aux.$person2->getId(), $aux.$person3->getId()];
        static::assertTrue($this->userService->isUserInOwners($user, $owners2));
    }

    public function testIsUserInGroups()
    {
        $user = new User();
        $user->setUsername('user1');
        $user->setEmail('user1@mail.com');
        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('group1');
        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('group2');
        $this->dm->persist($user);
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();

        $aux = 'first_second_';
        $groups = [$aux.$group1->getId(), $aux.$group2->getId()];

        static::assertFalse($this->userService->isUserInGroups($user, null, null, $groups));

        $user->addGroup($group2);
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertTrue($this->userService->isUserInGroups($user, null, null, $groups));

        $person = new Person();
        $person->setName('person test');
        $person->setEmail('person@mail.com');

        $mm = new MultimediaObject();
        $mm->setTitle('test');

        $this->dm->persist($person);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($this->userService->isUserInGroups($user, $mm->getId(), $person->getId(), $groups));

        $mm->addGroup($group1);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($this->userService->isUserInGroups($user, $mm->getId(), $person->getId(), $groups));

        $mm->addGroup($group2);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($this->userService->isUserInGroups($user, $mm->getId(), $person->getId(), $groups));
    }

    public function testAddGroupCasCas()
    {
        $casGroup = new Group();
        $casGroup->setKey('cas_key');
        $casGroup->setName('CAS Group');
        $casGroup->setOrigin('cas');

        $casUser = new User();
        $casUser->setUsername('cas_user');
        $casUser->setEmail('cas_user@mail.com');
        $casUser->setOrigin('cas');

        $this->dm->persist($casGroup);
        $this->dm->persist($casUser);
        $this->dm->flush();

        $this->userService->addGroup($casGroup, $casUser, true, false);

        static::assertTrue($casUser->containsGroup($casGroup));
    }

    public function testDeleteGroupCasCas()
    {
        $casGroup = new Group();
        $casGroup->setKey('cas_key');
        $casGroup->setName('Cas Group');
        $casGroup->setOrigin('cas');

        $casUser = new User();
        $casUser->setUsername('cas_user');
        $casUser->setEmail('cas_user@mail.com');
        $casUser->setOrigin('cas');

        $this->dm->persist($casGroup);
        $this->dm->persist($casUser);
        $this->dm->flush();

        $casUser->addGroup($casGroup);
        $this->dm->persist($casUser);
        $this->dm->flush();

        static::assertTrue($casUser->containsGroup($casGroup));

        $this->userService->deleteGroup($casGroup, $casUser, true, false);

        static::assertFalse($casUser->containsGroup($casGroup));
    }
}
