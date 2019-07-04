<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Services\PermissionProfileService;

class PermissionProfileServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $permissionProfileService;
    private $dispatcher;
    private $permissionService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository(PermissionProfile::class);
        $this->permissionProfileService = static::$kernel->getContainer()
          ->get('pumukitschema.permissionprofile');
        $this->dispatcher = static::$kernel->getContainer()
          ->get('pumukitschema.permissionprofile_dispatcher');
        $this->permissionService = static::$kernel->getContainer()
          ->get('pumukitschema.permission');

        $this->dm->getDocumentCollection(PermissionProfile::class)->remove([]);
        $this->dm->flush();

        $this->permissionProfileService = new PermissionProfileService($this->dm, $this->dispatcher, $this->permissionService);
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->permissionProfileService = null;
        $this->dispatcher = null;
        $this->permissionService = null;
        $this->permissionProfileService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testUpdate()
    {
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('test1');
        $permissionProfile1->setDefault(true);

        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('test2');
        $permissionProfile2->setDefault(false);

        $permissionProfile3 = new PermissionProfile();
        $permissionProfile3->setName('test3');
        $permissionProfile3->setDefault(false);

        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->persist($permissionProfile3);
        $this->dm->flush();

        $this->assertEquals($permissionProfile1, $this->repo->findOneByDefault(true));

        $falseDefault = $this->repo->findByDefault(false);
        $this->assertFalse(in_array($permissionProfile1, $falseDefault));
        $this->assertTrue(in_array($permissionProfile2, $falseDefault));
        $this->assertTrue(in_array($permissionProfile3, $falseDefault));

        $permissionProfile2->setDefault(true);
        $permissionProfile2 = $this->permissionProfileService->update($permissionProfile2);

        $this->assertEquals($permissionProfile2, $this->repo->findOneByDefault(true));

        $falseDefault = $this->repo->findByDefault(false);
        $this->assertTrue(in_array($permissionProfile1, $falseDefault));
        $this->assertFalse(in_array($permissionProfile2, $falseDefault));
        $this->assertTrue(in_array($permissionProfile3, $falseDefault));
    }

    public function testAddPermission()
    {
        $permissions = [
                            Permission::ACCESS_DASHBOARD,
                            Permission::ACCESS_MULTIMEDIA_SERIES,
                            ];

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');
        $permissionProfile->setPermissions($permissions);

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $this->assertEquals($permissions, $permissionProfile->getPermissions());

        $this->permissionProfileService->addPermission($permissionProfile, 'NON_EXISTING_PERMISSION');
        $this->assertEquals($permissions, $permissionProfile->getPermissions());

        $this->permissionProfileService->addPermission($permissionProfile, Permission::ACCESS_ROLES);

        $newPermissions = [
                               Permission::ACCESS_DASHBOARD,
                               Permission::ACCESS_MULTIMEDIA_SERIES,
                               Permission::ACCESS_ROLES,
                               ];

        $falsePermissions = [
                                 Permission::ACCESS_DASHBOARD,
                                 Permission::ACCESS_MULTIMEDIA_SERIES,
                                 Permission::ACCESS_LIVE_EVENTS,
                                 ];

        $this->assertEquals($newPermissions, $permissionProfile->getPermissions());
        $this->assertNotEquals($falsePermissions, $permissionProfile->getPermissions());
    }

    public function testRemovePermission()
    {
        $permissions = [
                            Permission::ACCESS_DASHBOARD,
                            Permission::ACCESS_MULTIMEDIA_SERIES,
                            ];

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');
        $permissionProfile->setPermissions($permissions);

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $this->assertEquals($permissions, $permissionProfile->getPermissions());

        $this->permissionProfileService->removePermission($permissionProfile, 'NON_EXISTING_PERMISSION');
        $this->assertEquals($permissions, $permissionProfile->getPermissions());

        $this->permissionProfileService->removePermission($permissionProfile, Permission::ACCESS_MULTIMEDIA_SERIES);

        $newPermissions = [Permission::ACCESS_DASHBOARD];

        $this->assertEquals($newPermissions, $permissionProfile->getPermissions());
        $this->assertNotEquals($permissions, $permissionProfile->getPermissions());
    }

    public function testCheckDefault()
    {
        $this->assertCount(0, $this->repo->findByDefault(true));
        $this->assertCount(0, $this->repo->findByDefault(false));

        $permissions1 = [Permission::ACCESS_DASHBOARD];
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('test1');
        $permissionProfile1->setSystem(true);
        $permissionProfile1->setDefault(true);
        $permissionProfile1->setPermissions($permissions1);

        $permissions2 = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_ADVANCED_UPLOAD];
        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('test2');
        $permissionProfile2->setSystem(true);
        $permissionProfile2->setDefault(false);
        $permissionProfile2->setPermissions($permissions2);

        $permissions3 = [];
        $permissionProfile3 = new PermissionProfile();
        $permissionProfile3->setName('test3');
        $permissionProfile3->setSystem(true);
        $permissionProfile3->setDefault(false);
        $permissionProfile3->setPermissions($permissions3);

        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->persist($permissionProfile3);
        $this->dm->flush();

        $this->assertCount(1, $this->repo->findByDefault(true));
        $this->assertCount(2, $this->repo->findByDefault(false));
        $this->assertEquals($permissionProfile1, $this->repo->findOneByDefault(true));

        $falseDefault = $this->repo->findByDefault(false);
        $this->assertFalse(in_array($permissionProfile1, $falseDefault));
        $this->assertTrue(in_array($permissionProfile2, $falseDefault));
        $this->assertTrue(in_array($permissionProfile3, $falseDefault));

        $permissionProfile1->setDefault(false);
        $permissionProfile1 = $this->permissionProfileService->update($permissionProfile1);

        $this->assertEquals($permissionProfile3, $this->repo->findOneByDefault(true));

        $falseDefault = $this->repo->findByDefault(false);
        $this->assertTrue(in_array($permissionProfile1, $falseDefault));
        $this->assertTrue(in_array($permissionProfile2, $falseDefault));
        $this->assertFalse(in_array($permissionProfile3, $falseDefault));

        $permissionProfile4 = new PermissionProfile();
        $permissionProfile4->setName('test4');
        $permissionProfile4->setSystem(false);
        $permissionProfile4->setDefault(false);

        $this->dm->persist($permissionProfile4);
        $this->dm->flush();

        $permissionProfile4->setDefault(true);
        $permissionProfile4 = $this->permissionProfileService->update($permissionProfile4);

        $this->assertCount(1, $this->repo->findByDefault(true));
        $this->assertCount(3, $this->repo->findByDefault(false));
        $this->assertEquals($permissionProfile4, $this->repo->findOneByDefault(true));
    }

    public function testSetDefaultPermissionProfile()
    {
        $this->assertFalse($this->permissionProfileService->setDefaultPermissionProfile());

        $permissions1 = [Permission::ACCESS_DASHBOARD];
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('test1');
        $permissionProfile1->setDefault(false);
        $permissionProfile1->setPermissions($permissions1);

        $permissions2 = [];
        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('test2');
        $permissionProfile2->setDefault(false);
        $permissionProfile2->setPermissions($permissions2);

        $permissions3 = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_ADVANCED_UPLOAD];
        $permissionProfile3 = new PermissionProfile();
        $permissionProfile3->setName('test3');
        $permissionProfile3->setDefault(false);
        $permissionProfile3->setPermissions($permissions3);

        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->persist($permissionProfile3);
        $this->dm->flush();

        $this->assertEquals($permissionProfile2, $this->permissionProfileService->setDefaultPermissionProfile());
    }

    public function testSetScope()
    {
        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $permissionProfile = $this->repo->find($permissionProfile->getId());
        $this->assertEquals(PermissionProfile::SCOPE_PERSONAL, $permissionProfile->getScope());

        $permissionProfile = $this->repo->find($permissionProfile->getId());
        $this->permissionProfileService->setScope($permissionProfile, PermissionProfile::SCOPE_NONE);
        $this->assertEquals(PermissionProfile::SCOPE_NONE, $permissionProfile->getScope());

        $permissionProfile = $this->repo->find($permissionProfile->getId());
        $this->permissionProfileService->setScope($permissionProfile, 'non existing scope');
        $this->assertEquals(PermissionProfile::SCOPE_NONE, $permissionProfile->getScope());
    }

    public function testGetDefault()
    {
        $this->assertNull($this->permissionProfileService->getDefault());

        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('test1');
        $permissionProfile1->setDefault(false);

        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('test2');
        $permissionProfile2->setDefault(true);

        $permissionProfile3 = new PermissionProfile();
        $permissionProfile3->setName('test3');
        $permissionProfile3->setDefault(false);

        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->persist($permissionProfile3);
        $this->dm->flush();

        $this->assertEquals($permissionProfile2, $this->permissionProfileService->getDefault());
    }
}
