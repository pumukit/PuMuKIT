<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;

class UserServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $permissionProfileRepo;
    private $userService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository('PumukitSchemaBundle:User');
        $this->permissionProfileRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:PermissionProfile');
        $this->userService = $kernel->getContainer()
          ->get('pumukitschema.user');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:User')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:PermissionProfile')->remove(array());
        $this->dm->flush();
    }

    public function testCreateAndUpdate()
    {
        $permissions1 = array(Permission::ACCESS_DASHBOARD, Permission::ACCESS_ROLES);
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setPermissions($permissions1);
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

        $permissions2 = array(Permission::ACCESS_TAGS);
        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setPermissions($permissions2);
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
    }

    public function testAddAndRemoveRoles()
    {
        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@mail.com');

        $this->dm->persist($user);
        $this->dm->flush();

        $permissions1 = array(Permission::ACCESS_DASHBOARD, Permission::ACCESS_ROLES);
        $user = $this->userService->addRoles($user, $permissions1);

        $user = $this->repo->find($user->getId());

        $this->assertTrue($user->hasRole(Permission::ACCESS_DASHBOARD));
        $this->assertTrue($user->hasRole(Permission::ACCESS_ROLES));
        $this->assertFalse($user->hasRole(Permission::ACCESS_TAGS));

        $permissions2 = array(Permission::ACCESS_TAGS, Permission::ACCESS_ROLES);
        $user = $this->userService->removeRoles($user, $permissions2);

        $user = $this->repo->find($user->getId());

        $this->assertTrue($user->hasRole(Permission::ACCESS_DASHBOARD));
        $this->assertFalse($user->hasRole(Permission::ACCESS_ROLES));
        $this->assertFalse($user->hasRole(Permission::ACCESS_TAGS));
    }
}