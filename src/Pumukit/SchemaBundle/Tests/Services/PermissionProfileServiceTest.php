<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\PermissionProfileService;

/**
 * @internal
 * @coversNothing
 */
class PermissionProfileServiceTest extends PumukitTestCase
{
    private $repo;
    private $permissionProfileService;
    private $dispatcher;
    private $permissionService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->repo = $this->dm->getRepository(PermissionProfile::class);
        $this->permissionProfileService = static::$kernel->getContainer()->get('pumukitschema.permissionprofile');
        $this->dispatcher = static::$kernel->getContainer()->get('pumukitschema.permissionprofile_dispatcher');
        $this->permissionService = static::$kernel->getContainer()->get('pumukitschema.permission');
        $this->permissionProfileService = new PermissionProfileService($this->dm, $this->dispatcher, $this->permissionService);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->permissionProfileService = null;
        $this->dispatcher = null;
        $this->permissionService = null;
        $this->permissionProfileService = null;
        gc_collect_cycles();
    }

    public function testUpdate(): void
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

        static::assertEquals($permissionProfile1, $this->repo->findOneBy(['default' => true]));

        $falseDefault = $this->repo->findBy(['default' => false]);
        static::assertNotContains($permissionProfile1, $falseDefault);
        static::assertContains($permissionProfile2, $falseDefault);
        static::assertContains($permissionProfile3, $falseDefault);

        $permissionProfile2->setDefault(true);
        $permissionProfile2 = $this->permissionProfileService->update($permissionProfile2);

        static::assertEquals($permissionProfile2, $this->repo->findOneBy(['default' => true]));

        $falseDefault = $this->repo->findBy(['default' => false]);
        static::assertContains($permissionProfile1, $falseDefault);
        static::assertNotContains($permissionProfile2, $falseDefault);
        static::assertContains($permissionProfile3, $falseDefault);
    }

    public function testAddPermission(): void
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

        static::assertEquals($permissions, $permissionProfile->getPermissions());

        $this->permissionProfileService->addPermission($permissionProfile, 'NON_EXISTING_PERMISSION');
        static::assertEquals($permissions, $permissionProfile->getPermissions());

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

        static::assertEquals($newPermissions, $permissionProfile->getPermissions());
        static::assertNotEquals($falsePermissions, $permissionProfile->getPermissions());
    }

    public function testRemovePermission(): void
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

        static::assertEquals($permissions, $permissionProfile->getPermissions());

        $this->permissionProfileService->removePermission($permissionProfile, 'NON_EXISTING_PERMISSION');
        static::assertEquals($permissions, $permissionProfile->getPermissions());

        $this->permissionProfileService->removePermission($permissionProfile, Permission::ACCESS_MULTIMEDIA_SERIES);

        $newPermissions = [Permission::ACCESS_DASHBOARD];

        static::assertEquals($newPermissions, $permissionProfile->getPermissions());
        static::assertNotEquals($permissions, $permissionProfile->getPermissions());
    }

    public function testCheckDefault(): void
    {
        static::assertCount(0, $this->repo->findBy(['default' => true]));
        static::assertCount(0, $this->repo->findBy(['default' => false]));

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

        static::assertCount(1, $this->repo->findBy(['default' => true]));
        static::assertCount(2, $this->repo->findBy(['default' => false]));
        static::assertEquals($permissionProfile1, $this->repo->findOneBy(['default' => true]));

        $falseDefault = $this->repo->findBy(['default' => false]);
        static::assertNotContains($permissionProfile1, $falseDefault);
        static::assertContains($permissionProfile2, $falseDefault);
        static::assertContains($permissionProfile3, $falseDefault);

        $permissionProfile1->setDefault(false);
        $permissionProfile1 = $this->permissionProfileService->update($permissionProfile1);

        static::assertEquals($permissionProfile3, $this->repo->findOneBy(['default' => true]));

        $falseDefault = $this->repo->findBy(['default' => false]);
        static::assertContains($permissionProfile1, $falseDefault);
        static::assertContains($permissionProfile2, $falseDefault);
        static::assertNotContains($permissionProfile3, $falseDefault);

        $permissionProfile4 = new PermissionProfile();
        $permissionProfile4->setName('test4');
        $permissionProfile4->setSystem(false);
        $permissionProfile4->setDefault(false);

        $this->dm->persist($permissionProfile4);
        $this->dm->flush();

        $permissionProfile4->setDefault(true);
        $permissionProfile4 = $this->permissionProfileService->update($permissionProfile4);

        static::assertCount(1, $this->repo->findBy(['default' => true]));
        static::assertCount(3, $this->repo->findBy(['default' => false]));
        static::assertEquals($permissionProfile4, $this->repo->findOneBy(['default' => true]));
    }

    public function testSetDefaultPermissionProfile(): void
    {
        static::assertNull($this->permissionProfileService->setDefaultPermissionProfile());

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

        static::assertEquals($permissionProfile2, $this->permissionProfileService->setDefaultPermissionProfile());
    }

    public function testSetScope(): void
    {
        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $permissionProfile = $this->repo->find($permissionProfile->getId());
        static::assertEquals(PermissionProfile::SCOPE_PERSONAL, $permissionProfile->getScope());

        $permissionProfile = $this->repo->find($permissionProfile->getId());
        $this->permissionProfileService->setScope($permissionProfile, PermissionProfile::SCOPE_NONE);
        static::assertEquals(PermissionProfile::SCOPE_NONE, $permissionProfile->getScope());

        $permissionProfile = $this->repo->find($permissionProfile->getId());
        $this->permissionProfileService->setScope($permissionProfile, 'non existing scope');
        static::assertEquals(PermissionProfile::SCOPE_NONE, $permissionProfile->getScope());
    }

    public function testGetDefault(): void
    {
        static::assertNull($this->permissionProfileService->getDefault());

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

        static::assertEquals($permissionProfile2, $this->permissionProfileService->getDefault());
    }
}
