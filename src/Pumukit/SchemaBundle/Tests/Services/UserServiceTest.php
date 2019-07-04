<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\EventListener\PermissionProfileListener;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\PermissionProfileEventDispatcherService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PermissionService;
use Pumukit\SchemaBundle\Services\UserEventDispatcherService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
class UserServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $permissionProfileRepo;
    private $userService;
    private $logger;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(User::class);
        $this->permissionProfileRepo = $this->dm->getRepository(PermissionProfile::class);

        $dispatcher = new EventDispatcher();
        $userDispatcher = new UserEventDispatcherService($dispatcher);
        $permissionProfileDispatcher = new PermissionProfileEventDispatcherService($dispatcher);
        $permissionService = new PermissionService($this->dm);
        $permissionProfileService = new PermissionProfileService(
            $this->dm,
            $permissionProfileDispatcher,
            $permissionService
        );
        $this->logger = static::$kernel->getContainer()
            ->get('logger')
        ;

        $personalScopeDeleteOwners = false;

        $this->userService = new UserService(
            $this->dm,
            $userDispatcher,
            $permissionService,
            $permissionProfileService,
            $personalScopeDeleteOwners
        );

        $listener = new PermissionProfileListener($this->dm, $this->userService, $this->logger);
        $dispatcher->addListener('permissionprofile.update', [$listener, 'postUpdate']);

        $this->dm->getDocumentCollection(User::class)->remove([]);
        $this->dm->getDocumentCollection(Group::class)->remove([]);
        $this->dm->getDocumentCollection(PermissionProfile::class)->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->permissionProfileRepo = null;
        $this->userService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testCreateAndUpdate()
    {
        $permissions1 = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_ROLES];
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setPermissions($permissions1);
        $permissionProfile1->setName('permissionprofile1');
        $permissionProfile1->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        $username = 'test';
        $email = 'test@mail.com';
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPermissionProfile($permissionProfile1);

        $user = $this->userService->create($user);

        $user = $this->repo->find($user->getId());
        $permissionProfile1 = $this->permissionProfileRepo->find($permissionProfile1->getId());

        $this->assertEquals($permissionProfile1, $user->getPermissionProfile());
        $this->assertTrue($user->hasRole(Permission::ACCESS_DASHBOARD));
        $this->assertTrue($user->hasRole(Permission::ACCESS_ROLES));
        $this->assertFalse($user->hasRole(Permission::ACCESS_TAGS));
        $this->assertFalse($user->hasRole(PermissionProfile::SCOPE_GLOBAL));
        $this->assertTrue($user->hasRole(PermissionProfile::SCOPE_PERSONAL));
        $this->assertFalse($user->hasRole(PermissionProfile::SCOPE_NONE));

        $permissions2 = [Permission::ACCESS_TAGS];
        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setPermissions($permissions2);
        $permissionProfile2->setName('permissionprofile2');
        $permissionProfile2->setScope(PermissionProfile::SCOPE_GLOBAL);
        $this->dm->persist($permissionProfile2);
        $this->dm->flush();

        $user->setPermissionProfile($permissionProfile2);

        $user = $this->userService->update($user);

        $user = $this->repo->find($user->getId());
        $permissionProfile2 = $this->permissionProfileRepo->find($permissionProfile2->getId());

        $this->assertNotEquals($permissionProfile1, $user->getPermissionProfile());
        $this->assertEquals($permissionProfile2, $user->getPermissionProfile());
        $this->assertFalse($user->hasRole(Permission::ACCESS_DASHBOARD));
        $this->assertFalse($user->hasRole(Permission::ACCESS_ROLES));
        $this->assertTrue($user->hasRole(Permission::ACCESS_TAGS));
        $this->assertTrue($user->hasRole(PermissionProfile::SCOPE_GLOBAL));
        $this->assertFalse($user->hasRole(PermissionProfile::SCOPE_PERSONAL));
        $this->assertFalse($user->hasRole(PermissionProfile::SCOPE_NONE));
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

        $this->assertTrue($user->hasRole(Permission::ACCESS_DASHBOARD));
        $this->assertTrue($user->hasRole(Permission::ACCESS_ROLES));
        $this->assertFalse($user->hasRole(Permission::ACCESS_TAGS));

        $permissions2 = [Permission::ACCESS_TAGS, Permission::ACCESS_ROLES];
        $user = $this->userService->removeRoles($user, $permissions2);

        $user = $this->repo->find($user->getId());

        $this->assertTrue($user->hasRole(Permission::ACCESS_DASHBOARD));
        $this->assertFalse($user->hasRole(Permission::ACCESS_ROLES));
        $this->assertFalse($user->hasRole(Permission::ACCESS_TAGS));
    }

    public function testCountAndGetUsersWithPermissionProfile()
    {
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('permissionprofile1');
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('permissionprofile2');
        $this->dm->persist($permissionProfile2);
        $this->dm->flush();

        $user1 = new User();
        $user1->setUsername('test1');
        $user1->setEmail('test1@mail.com');
        $user1->setPermissionProfile($permissionProfile1);
        $user1 = $this->userService->create($user1);

        $user2 = new User();
        $user2->setUsername('test2');
        $user2->setEmail('test2@mail.com');
        $user2->setPermissionProfile($permissionProfile2);
        $user2 = $this->userService->create($user2);

        $user3 = new User();
        $user3->setUsername('test3');
        $user3->setEmail('test3@mail.com');
        $user3->setPermissionProfile($permissionProfile1);
        $user3 = $this->userService->create($user3);

        $this->assertEquals(2, $this->userService->countUsersWithPermissionProfile($permissionProfile1));
        $this->assertEquals(1, $this->userService->countUsersWithPermissionProfile($permissionProfile2));

        $usersProfile1 = $this->userService->getUsersWithPermissionProfile($permissionProfile1)->toArray();
        $this->assertTrue(in_array($user1, $usersProfile1));
        $this->assertFalse(in_array($user2, $usersProfile1));
        $this->assertTrue(in_array($user3, $usersProfile1));

        $usersProfile2 = $this->userService->getUsersWithPermissionProfile($permissionProfile2)->toArray();
        $this->assertFalse(in_array($user1, $usersProfile2));
        $this->assertTrue(in_array($user2, $usersProfile2));
        $this->assertFalse(in_array($user3, $usersProfile2));
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

        $this->assertNotEquals($permissions, $user->getRoles());
        $this->assertEquals($permissions, $this->userService->getUserPermissions($user->getRoles()));
    }

    public function testAddUserScope()
    {
        $notValidScope = 'NOT_VALID_SCOPE';

        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@mail.com');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertFalse(in_array(PermissionProfile::SCOPE_GLOBAL, $user->getRoles()));
        $this->assertFalse(in_array(PermissionProfile::SCOPE_PERSONAL, $user->getRoles()));
        $this->assertFalse(in_array(PermissionProfile::SCOPE_NONE, $user->getRoles()));

        $user = $this->userService->addUserScope($user, PermissionProfile::SCOPE_PERSONAL);

        $this->assertFalse(in_array(PermissionProfile::SCOPE_GLOBAL, $user->getRoles()));
        $this->assertTrue(in_array(PermissionProfile::SCOPE_PERSONAL, $user->getRoles()));
        $this->assertFalse(in_array(PermissionProfile::SCOPE_NONE, $user->getRoles()));
        $this->assertFalse(in_array($notValidScope, $user->getRoles()));

        $user = $this->userService->addUserScope($user, $notValidScope);

        $this->assertFalse(in_array(PermissionProfile::SCOPE_GLOBAL, $user->getRoles()));
        $this->assertTrue(in_array(PermissionProfile::SCOPE_PERSONAL, $user->getRoles()));
        $this->assertFalse(in_array(PermissionProfile::SCOPE_NONE, $user->getRoles()));
        $this->assertFalse(in_array($notValidScope, $user->getRoles()));
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

        $this->assertNotEquals(PermissionProfile::SCOPE_GLOBAL, $userScope);
        $this->assertNotEquals(PermissionProfile::SCOPE_PERSONAL, $userScope);
        $this->assertNotEquals(PermissionProfile::SCOPE_NONE, $userScope);
        $this->assertCount(1, $user->getRoles());

        $user = $this->userService->addRoles($user, $permissionProfile->getPermissions());
        $this->assertCount(3, $user->getRoles());
        $user = $this->userService->addUserScope($user, PermissionProfile::SCOPE_PERSONAL);
        $this->assertCount(4, $user->getRoles());

        $userScope = $this->userService->getUserScope($user->getRoles());

        $this->assertNotEquals(PermissionProfile::SCOPE_GLOBAL, $userScope);
        $this->assertEquals(PermissionProfile::SCOPE_PERSONAL, $userScope);
        $this->assertNotEquals(PermissionProfile::SCOPE_NONE, $userScope);
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

        $this->assertCount(1, $user->getRoles());
        $user = $this->userService->addRoles($user, $permissionProfile->getPermissions());
        $this->assertCount(3, $user->getRoles());
        $user = $this->userService->addUserScope($user, PermissionProfile::SCOPE_PERSONAL);
        $this->assertCount(4, $user->getRoles());

        $userScope = $this->userService->getUserScope($user->getRoles());

        $this->assertNotEquals(PermissionProfile::SCOPE_GLOBAL, $userScope);
        $this->assertEquals(PermissionProfile::SCOPE_PERSONAL, $userScope);
        $this->assertNotEquals(PermissionProfile::SCOPE_NONE, $userScope);

        $user = $this->userService->setUserScope($user, $userScope, PermissionProfile::SCOPE_GLOBAL);

        $newUserScope = $this->userService->getUserScope($user->getRoles());

        $this->assertEquals(PermissionProfile::SCOPE_GLOBAL, $newUserScope);
        $this->assertNotEquals(PermissionProfile::SCOPE_PERSONAL, $newUserScope);
        $this->assertNotEquals(PermissionProfile::SCOPE_NONE, $newUserScope);
        $this->assertCount(4, $user->getRoles());
    }

    public function testDelete()
    {
        $username = 'test';
        $email = 'test@mail.com';
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);

        $user = $this->userService->create($user);

        $this->assertCount(1, $this->repo->findAll());

        $user = $this->userService->delete($user);

        $this->assertCount(0, $this->repo->findAll());
    }

    public function testInstantiate()
    {
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('1');
        $permissionProfile1->setDefault(false);

        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('2');
        $permissionProfile2->setDefault(true);

        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->flush();

        $user1 = $this->userService->instantiate();

        $this->assertNull($user1->getUsername());
        $this->assertNull($user1->getEmail());
        $this->assertTrue($user1->isEnabled());
        $this->assertNotEquals($permissionProfile1, $user1->getPermissionProfile());
        $this->assertEquals($permissionProfile2, $user1->getPermissionProfile());

        $userName = 'test';
        $email = 'test@mail.com';
        $enabled = false;

        $permissionProfile1->setDefault(true);
        $permissionProfile2->setDefault(false);
        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->flush();

        $user2 = $this->userService->instantiate($userName, $email, $enabled);

        $this->assertEquals($userName, $user2->getUsername());
        $this->assertEquals($email, $user2->getEmail());
        $this->assertFalse($user2->isEnabled());
        $this->assertEquals($permissionProfile1, $user2->getPermissionProfile());
        $this->assertNotEquals($permissionProfile2, $user2->getPermissionProfile());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to assign a Permission Profile to the new User. There is no default Permission Profile
     */
    public function testInstantiateException()
    {
        $user = $this->userService->instantiate();
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

        $this->assertTrue($this->userService->hasGlobalScope($user));
        $this->assertFalse($this->userService->hasPersonalScope($user));
        $this->assertFalse($this->userService->hasNoneScope($user));

        $user->setPermissionProfile($personalProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertFalse($this->userService->hasGlobalScope($user));
        $this->assertTrue($this->userService->hasPersonalScope($user));
        $this->assertFalse($this->userService->hasNoneScope($user));

        $user->setPermissionProfile($noneProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertFalse($this->userService->hasGlobalScope($user));
        $this->assertFalse($this->userService->hasPersonalScope($user));
        $this->assertTrue($this->userService->hasNoneScope($user));
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

        $this->assertEquals(0, count($user->getGroups()));
        $this->assertFalse($user->containsGroup($group1));
        $this->assertFalse($user->containsGroup($group2));

        $this->userService->addGroup($group1, $user);

        $this->assertEquals(1, count($user->getGroups()));
        $this->assertTrue($user->containsGroup($group1));
        $this->assertFalse($user->containsGroup($group2));

        $this->userService->addGroup($group1, $user);

        $this->assertEquals(1, count($user->getGroups()));
        $this->assertTrue($user->containsGroup($group1));
        $this->assertFalse($user->containsGroup($group2));
        $this->assertFalse($user->containsGroup($group3));

        $this->userService->addGroup($group2, $user);

        $this->assertEquals(2, count($user->getGroups()));
        $this->assertTrue($user->containsGroup($group1));
        $this->assertTrue($user->containsGroup($group2));
        $this->assertFalse($user->containsGroup($group3));

        $this->userService->addGroup($group3, $user);

        $this->assertEquals(3, count($user->getGroups()));
        $this->assertTrue($user->containsGroup($group1));
        $this->assertTrue($user->containsGroup($group2));
        $this->assertTrue($user->containsGroup($group3));
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

        $this->assertEquals(0, count($user->getGroups()));
        $this->assertFalse($user->containsGroup($group1));
        $this->assertFalse($user->containsGroup($group2));

        $this->userService->addGroup($group1, $user);

        $user = $this->repo->find($user->getId());

        $this->assertEquals(1, count($user->getGroups()));
        $this->assertTrue($user->containsGroup($group1));
        $this->assertFalse($user->containsGroup($group2));

        $this->userService->deleteGroup($group1, $user);

        $user = $this->repo->find($user->getId());

        $this->assertEquals(0, count($user->getGroups()));
        $this->assertFalse($user->containsGroup($group1));
        $this->assertFalse($user->containsGroup($group2));
        $this->assertFalse($user->containsGroup($group3));

        $this->userService->deleteGroup($group2, $user);

        $user = $this->repo->find($user->getId());

        $this->assertEquals(0, count($user->getGroups()));
        $this->assertFalse($user->containsGroup($group1));
        $this->assertFalse($user->containsGroup($group2));
        $this->assertFalse($user->containsGroup($group3));

        $this->userService->addGroup($group3, $user);

        $user = $this->repo->find($user->getId());

        $this->assertEquals(1, count($user->getGroups()));
        $this->assertFalse($user->containsGroup($group1));
        $this->assertFalse($user->containsGroup($group2));
        $this->assertTrue($user->containsGroup($group3));

        $this->userService->deleteGroup($group3, $user);

        $user = $this->repo->find($user->getId());

        $this->assertEquals(0, count($user->getGroups()));
        $this->assertFalse($user->containsGroup($group1));
        $this->assertFalse($user->containsGroup($group2));
        $this->assertFalse($user->containsGroup($group3));
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

        $this->assertTrue($this->userService->isAllowedToModifyUserGroup($localUser, $localGroup));
        $this->assertFalse($this->userService->isAllowedToModifyUserGroup($casUser, $casGroup));
        $this->assertTrue($this->userService->isAllowedToModifyUserGroup($localUser, $casGroup));
        $this->assertTrue($this->userService->isAllowedToModifyUserGroup($casUser, $localGroup));
    }

    /**
     * @expectedException         \Exception
     * @expectedExceptionMessage  is not local and can not be modified
     */
    public function testUpdateException()
    {
        $permissions1 = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_ROLES];
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setPermissions($permissions1);
        $permissionProfile1->setName('permissionprofile1');
        $permissionProfile1->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        $username = 'test';
        $email = 'test@mail.com';
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPermissionProfile($permissionProfile1);
        $user->setOrigin('cas');

        $user = $this->userService->create($user);
        $user->setUsername('test2');
        $user = $this->userService->update($user);
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

        $this->assertTrue($casUser->containsGroup($localGroup));
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

        $this->assertTrue($localUser->containsGroup($casGroup));
    }

    /**
     * @expectedException         \Exception
     * @expectedExceptionMessage  Not allowed to add group
     */
    public function testExceptionAddGroupCasCas()
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

        $this->assertTrue($casUser->containsGroup($localGroup));

        $this->userService->deleteGroup($localGroup, $casUser);

        $this->assertFalse($casUser->containsGroup($localGroup));
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

        $this->assertTrue($localUser->containsGroup($casGroup));

        $this->userService->deleteGroup($casGroup, $localUser);

        $this->assertFalse($localUser->containsGroup($casGroup));
    }

    /**
     * @expectedException         \Exception
     * @expectedExceptionMessage  Not allowed to delete group
     */
    public function testExceptionDeleteGroupCasCas()
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
        $this->assertTrue(in_array($localUser, $usersLocalGroup));
        $this->assertFalse(in_array($casUser, $usersLocalGroup));
        $this->assertFalse(in_array($localUser, $usersCasGroup));
        $this->assertTrue(in_array($casUser, $usersCasGroup));
    }

    public function testDeleteAllFromGroup()
    {
        $group = new Group();
        $group->setKey('key');
        $group->setName('group');
        $this->dm->persist($group);
        $this->dm->flush();

        $this->assertEquals(0, count($this->userService->findWithGroup($group)->toArray()));

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

        $this->assertEquals(3, count($this->userService->findWithGroup($group)->toArray()));

        $this->userService->deleteAllFromGroup($group);
        $this->assertEquals(0, count($this->userService->findWithGroup($group)->toArray()));
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

        $this->assertFalse($this->userService->isUserLastRelation($user, null, $person1->getId(), $owners1, $groups));
        $this->assertFalse($this->userService->isUserLastRelation($user, null, $person2->getId(), $owners1, $groups));
        $this->assertFalse($this->userService->isUserLastRelation($user, null, $person3->getId(), $owners1, $groups));

        $permissionProfile1->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        $this->assertFalse($this->userService->isUserLastRelation($user, null, $person1->getId(), $owners1, $groups));
        $this->assertTrue($this->userService->isUserLastRelation($user, null, $person2->getId(), $owners1, $groups));
        $this->assertFalse($this->userService->isUserLastRelation($user, null, $person3->getId(), $owners1, $groups));

        $user->addGroup($group2);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertFalse($this->userService->isUserLastRelation($user, null, $person1->getId(), $owners1, $groups));
        $this->assertFalse($this->userService->isUserLastRelation($user, null, $person2->getId(), $owners1, $groups));
        $this->assertFalse($this->userService->isUserLastRelation($user, null, $person3->getId(), $owners1, $groups));

        $this->assertTrue($this->userService->isUserLastRelation($user, null, $person1->getId(), [], []));
        $this->assertTrue($this->userService->isUserLastRelation($user, null, $person2->getId(), [], []));
        $this->assertTrue($this->userService->isUserLastRelation($user, null, $person3->getId(), [], []));
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

        $this->assertFalse($this->userService->isLoggedPersonToRemoveFromOwner($user, $person1->getId()));
        $this->assertFalse($this->userService->isLoggedPersonToRemoveFromOwner($user, $person2->getId()));
        $this->assertFalse($this->userService->isLoggedPersonToRemoveFromOwner($user, $person3->getId()));

        $permissionProfile1->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        $this->assertFalse($this->userService->isLoggedPersonToRemoveFromOwner($user, $person1->getId()));
        $this->assertTrue($this->userService->isLoggedPersonToRemoveFromOwner($user, $person2->getId()));
        $this->assertFalse($this->userService->isLoggedPersonToRemoveFromOwner($user, $person3->getId()));
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
        $this->assertFalse($this->userService->isUserInOwners($user, $owners1));

        $owners2 = [$aux.$person1->getId(), $aux.$person2->getId(), $aux.$person3->getId()];
        $this->assertTrue($this->userService->isUserInOwners($user, $owners2));
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

        $this->assertFalse($this->userService->isUserInGroups($user, null, null, $groups));

        $user->addGroup($group2);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($this->userService->isUserInGroups($user, null, null, $groups));

        $person = new Person();
        $person->setName('person test');
        $person->setEmail('person@mail.com');

        $mm = new MultimediaObject();
        $mm->setTitle('test');

        $this->dm->persist($person);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($this->userService->isUserInGroups($user, $mm->getId(), $person->getId(), $groups));

        $mm->addGroup($group1);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($this->userService->isUserInGroups($user, $mm->getId(), $person->getId(), $groups));

        $mm->addGroup($group2);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->userService->isUserInGroups($user, $mm->getId(), $person->getId(), $groups));
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

        $this->assertTrue($casUser->containsGroup($casGroup));
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

        $this->assertTrue($casUser->containsGroup($casGroup));

        $this->userService->deleteGroup($casGroup, $casUser, true, false);

        $this->assertFalse($casUser->containsGroup($casGroup));
    }
}
