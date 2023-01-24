<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\EventListener;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
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
class PermissionProfileListenerTest extends PumukitTestCase
{
    private $userRepo;
    private $userService;
    private $permissionProfileService;
    private $listener;
    private $updateUserService;
    private $userPasswordEncoder;
    private $createUserService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->userRepo = $this->dm->getRepository(User::class);
        $dispatcher = new EventDispatcher();
        $userDispatcher = new UserEventDispatcherService($dispatcher);
        $permissionProfileDispatcher = new PermissionProfileEventDispatcherService($dispatcher);
        $multimediaObjectEventDispatcher = new MultimediaObjectEventDispatcherService($dispatcher);
        $permissionService = new PermissionService($this->dm);
        $this->permissionProfileService = new PermissionProfileService(
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
            $this->permissionProfileService,
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
            $this->permissionProfileService,
            $personService,
            $userDispatcher
        );

        $this->updateUserService = new UpdateUserService(
            $this->dm,
            $this->permissionProfileService,
            $this->userPasswordEncoder,
            $userDispatcher
        );

        $this->listener = new PermissionProfileListener($this->dm, $this->userService, $this->updateUserService);
        $dispatcher->addListener('permissionprofile.update', [$this->listener, 'postUpdate']);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->userRepo = null;
        $this->permissionProfileService = null;
        $this->userService = null;
        $this->listener = null;
        gc_collect_cycles();
    }

    public function testPostUpdate()
    {
        $permissions = [Permission::ACCESS_LIVE_CHANNELS];

        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('permissionprofile1');
        $permissionProfile1->setPermissions($permissions);
        $permissionProfile1->setDefault(true);
        $permissionProfile1->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile1);
        $this->dm->flush();

        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('permissionprofile2');
        $permissionProfile2->setPermissions($permissions);
        $permissionProfile2->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile2);
        $this->dm->flush();

        $user1 = $this->createUserService->createUser('test1', 'passwordExample', 'test1@mail.com', 'User name', $permissionProfile1);
        $user2 = $this->createUserService->createUser('test2', 'passwordExample', 'test2@mail.com', 'User name', $permissionProfile2);
        $user3 = $this->createUserService->createUser('test3', 'passwordExample', 'test3@mail.com', 'User name', $permissionProfile1);

        $user1Roles = $user1->getRoles();
        static::assertContains('ROLE_USER', $user1Roles);
        static::assertNotContains(Permission::ACCESS_DASHBOARD, $user1Roles);

        $user2Roles = $user2->getRoles();
        static::assertContains('ROLE_USER', $user2Roles);
        static::assertNotContains(Permission::ACCESS_DASHBOARD, $user2Roles);

        $user3Roles = $user3->getRoles();
        static::assertContains('ROLE_USER', $user3Roles);
        static::assertNotContains(Permission::ACCESS_DASHBOARD, $user3Roles);

        $permissionProfile1->addPermission(Permission::ACCESS_DASHBOARD);
        $this->permissionProfileService->update($permissionProfile1);

        // Necessary to remove cached permissions.
        $this->dm->clear();

        $user1 = $this->userRepo->findOneBy(['username' => 'test1']);
        $user1Roles = $user1->getRoles();
        static::assertContains('ROLE_USER', $user1Roles);
        static::assertContains(Permission::ACCESS_DASHBOARD, $user1Roles);

        $user2 = $this->userRepo->findOneBy(['username' => 'test2']);
        $user2Roles = $user2->getRoles();
        static::assertContains('ROLE_USER', $user2Roles);
        static::assertNotContains(Permission::ACCESS_DASHBOARD, $user2Roles);

        $user3 = $this->userRepo->findOneBy(['username' => 'test3']);
        $user3Roles = $user3->getRoles();
        static::assertContains('ROLE_USER', $user3Roles);
        static::assertContains(Permission::ACCESS_DASHBOARD, $user3Roles);
    }
}
