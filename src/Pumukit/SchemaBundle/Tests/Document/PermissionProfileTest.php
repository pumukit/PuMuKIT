<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;

/**
 * @internal
 * @coversNothing
 */
class PermissionProfileTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $name = 'User Test Permission';
        $permissions = [
            Permission::ACCESS_DASHBOARD,
            Permission::ACCESS_MULTIMEDIA_SERIES,
            Permission::ACCESS_LIVE_CHANNELS,
            Permission::ACCESS_LIVE_EVENTS,
            'ROLE_ACCESS_IMPORTER',
        ];
        $system = true;
        $default = true;
        $scope = PermissionProfile::SCOPE_GLOBAL;

        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);
        $permissionProfile->setPermissions($permissions);
        $permissionProfile->setSystem($system);
        $permissionProfile->setDefault($default);
        $permissionProfile->setScope($scope);

        $this->assertEquals($name, $permissionProfile->getName());
        $this->assertEquals($permissions, $permissionProfile->getPermissions());
        $this->assertEquals($system, $permissionProfile->getSystem());
        $this->assertEquals($default, $permissionProfile->getDefault());
        $this->assertEquals($scope, $permissionProfile->getScope());
    }

    public function testPermissionsCollection()
    {
        $name = 'User Test Permission';
        $permissions = [
            Permission::ACCESS_DASHBOARD,
            Permission::ACCESS_MULTIMEDIA_SERIES,
            Permission::ACCESS_LIVE_CHANNELS,
            Permission::ACCESS_LIVE_EVENTS,
            'ROLE_ACCESS_IMPORTER',
        ];
        $system = true;
        $default = true;
        $scope = PermissionProfile::SCOPE_GLOBAL;

        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);
        $permissionProfile->setPermissions($permissions);
        $permissionProfile->setSystem($system);
        $permissionProfile->setDefault($default);
        $permissionProfile->setScope($scope);

        $this->assertEquals($permissions, $permissionProfile->getPermissions());

        $this->assertTrue($permissionProfile->containsPermission(Permission::ACCESS_DASHBOARD));
        $this->assertFalse($permissionProfile->containsPermission(Permission::ACCESS_ADMIN_USERS));

        $this->assertTrue($permissionProfile->containsAllPermissions($permissions));

        $morePermissions = [
            Permission::ACCESS_DASHBOARD,
            Permission::ACCESS_MULTIMEDIA_SERIES,
            Permission::ACCESS_ADMIN_USERS,
        ];

        $fewerPermissions = [
            Permission::ACCESS_DASHBOARD,
            Permission::ACCESS_MULTIMEDIA_SERIES,
        ];

        $notPermissions = [
            Permission::ACCESS_ADMIN_USERS,
            Permission::ACCESS_ROLES,
        ];

        $this->assertFalse($permissionProfile->containsAllPermissions($morePermissions));
        $this->assertTrue($permissionProfile->containsAllPermissions($fewerPermissions));
        $this->assertTrue($permissionProfile->containsAnyPermission($fewerPermissions));
        $this->assertTrue($permissionProfile->containsAnyPermission($morePermissions));
        $this->assertFalse($permissionProfile->containsAnyPermission($notPermissions));

        $newPermissions = [
            Permission::ACCESS_DASHBOARD,
            Permission::ACCESS_MULTIMEDIA_SERIES,
            Permission::ACCESS_LIVE_CHANNELS,
            Permission::ACCESS_LIVE_EVENTS,
            'ROLE_ACCESS_IMPORTER',
            Permission::ACCESS_ADMIN_USERS,
        ];

        $this->assertEquals($newPermissions, $permissionProfile->addPermission(Permission::ACCESS_ADMIN_USERS));
        $this->assertTrue($permissionProfile->containsPermission(Permission::ACCESS_ADMIN_USERS));

        $this->assertTrue($permissionProfile->removePermission(Permission::ACCESS_DASHBOARD));
        $this->assertFalse($permissionProfile->containsPermission(Permission::ACCESS_DASHBOARD));

        $this->assertFalse($permissionProfile->removePermission(Permission::ACCESS_WIZARD_UPLOAD));
    }

    public function testIsScope()
    {
        $name = 'User Test Permission';
        $permissions = [
            Permission::ACCESS_DASHBOARD,
            Permission::ACCESS_MULTIMEDIA_SERIES,
            Permission::ACCESS_LIVE_CHANNELS,
            Permission::ACCESS_LIVE_EVENTS,
            'ROLE_ACCESS_IMPORTER',
        ];
        $system = true;
        $default = true;
        $scope = PermissionProfile::SCOPE_GLOBAL;

        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);
        $permissionProfile->setPermissions($permissions);
        $permissionProfile->setSystem($system);
        $permissionProfile->setDefault($default);
        $permissionProfile->setScope($scope);

        $this->assertTrue($permissionProfile->isGlobal());
        $this->assertFalse($permissionProfile->isPersonal());
        $this->assertFalse($permissionProfile->isNone());
    }

    public function testIsDefault()
    {
        $name = 'User Test Permission';
        $default = true;

        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);
        $permissionProfile->setDefault($default);

        $this->assertTrue($permissionProfile->isDefault());

        $name = 'User Test Permission 2';
        $default = false;

        $permissionProfile2 = new PermissionProfile();

        $permissionProfile2->setName($name);
        $permissionProfile2->setDefault($default);

        $this->assertFalse($permissionProfile2->isDefault());
    }

    public function testIsSystem()
    {
        $name = 'User Test Permission';
        $system = true;

        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);
        $permissionProfile->setSystem($system);

        $this->assertTrue($permissionProfile->isSystem());

        $name = 'User Test Permission 2';
        $system = false;

        $permissionProfile2 = new PermissionProfile();

        $permissionProfile2->setName($name);
        $permissionProfile2->setSystem($system);

        $this->assertFalse($permissionProfile2->isSystem());
    }

    public function testToString()
    {
        $name = 'User Test Permission';

        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);

        $this->assertEquals($name, $permissionProfile->__toString());
    }
}
