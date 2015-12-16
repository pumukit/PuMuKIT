<?php

namespace Pumukit\SchemaBundle\Tests\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Event\PermissionProfileEvent;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;

class PermissionProfileListenerTest extends WebTestCase
{
    private $dm;
    private $userRepo;
    private $permissionProfileRepo;
    private $userService;
    private $permissionProfileService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->userRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:User');
        $this->permissionProfileRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:PermissionProfile');
        $this->userService = $kernel->getContainer()->get('pumukitschema.user');
        $this->permissionProfileService = $kernel->getContainer()
          ->get('pumukitschema.permissionprofile');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:PermissionProfile')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:User')
          ->remove(array());
        $this->dm->flush();
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