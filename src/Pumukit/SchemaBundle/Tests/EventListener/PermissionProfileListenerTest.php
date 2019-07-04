<?php

namespace Pumukit\SchemaBundle\Tests\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\UserService;
use Pumukit\SchemaBundle\Services\UserEventDispatcherService;
use Pumukit\SchemaBundle\Services\PermissionService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PermissionProfileEventDispatcherService;
use Pumukit\SchemaBundle\EventListener\PermissionProfileListener;

class PermissionProfileListenerTest extends WebTestCase
{
    private $dm;
    private $userRepo;
    private $permissionProfileRepo;
    private $userService;
    private $permissionProfileService;
    private $listener;
    private $logger;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->userRepo = $this->dm->getRepository(User::class);
        $this->permissionProfileRepo = $this->dm->getRepository(PermissionProfile::class);

        $dispatcher = new EventDispatcher();
        $userDispatcher = new UserEventDispatcherService($dispatcher);
        $permissionProfileDispatcher = new PermissionProfileEventDispatcherService($dispatcher);
        $permissionService = new PermissionService($this->dm);
        $this->permissionProfileService = new PermissionProfileService(
            $this->dm, $permissionProfileDispatcher,
            $permissionService
        );

        $personalScopeDeleteOwners = false;

        $this->userService = new UserService(
            $this->dm, $userDispatcher,
            $permissionService, $this->permissionProfileService,
            $personalScopeDeleteOwners
        );
        $this->logger = static::$kernel->getContainer()
            ->get('logger');

        $this->listener = new PermissionProfileListener($this->dm, $this->userService, $this->logger);
        $dispatcher->addListener('permissionprofile.update', [$this->listener, 'postUpdate']);

        $this->dm->getDocumentCollection(PermissionProfile::class)
          ->remove([]);
        $this->dm->getDocumentCollection(User::class)
          ->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm = null;
        $this->userRepo = null;
        $this->permissionProfileRepo = null;
        $this->permissionProfileService = null;
        $this->userService = null;
        $this->listener = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testPostUpdate()
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

        $user1Roles = $user1->getRoles();
        $this->assertTrue(in_array('ROLE_USER', $user1Roles));
        $this->assertFalse(in_array(Permission::ACCESS_DASHBOARD, $user1Roles));

        $user2Roles = $user2->getRoles();
        $this->assertTrue(in_array('ROLE_USER', $user2Roles));
        $this->assertFalse(in_array(Permission::ACCESS_DASHBOARD, $user2Roles));

        $user3Roles = $user3->getRoles();
        $this->assertTrue(in_array('ROLE_USER', $user3Roles));
        $this->assertFalse(in_array(Permission::ACCESS_DASHBOARD, $user3Roles));

        $permissionProfile1->addPermission(Permission::ACCESS_DASHBOARD);
        $this->permissionProfileService->update($permissionProfile1);

        $user1Roles = $user1->getRoles();
        $this->assertTrue(in_array('ROLE_USER', $user1Roles));
        $this->assertTrue(in_array(Permission::ACCESS_DASHBOARD, $user1Roles));

        $user2Roles = $user2->getRoles();
        $this->assertTrue(in_array('ROLE_USER', $user2Roles));
        $this->assertFalse(in_array(Permission::ACCESS_DASHBOARD, $user2Roles));

        $user3Roles = $user3->getRoles();
        $this->assertTrue(in_array('ROLE_USER', $user3Roles));
        $this->assertTrue(in_array(Permission::ACCESS_DASHBOARD, $user3Roles));
    }
}
