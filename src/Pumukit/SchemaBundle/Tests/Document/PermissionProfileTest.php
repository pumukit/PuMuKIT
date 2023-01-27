<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;

/**
 * @internal
 *
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

        static::assertEquals($name, $permissionProfile->getName());
        static::assertEquals($permissions, $permissionProfile->getPermissions());
        static::assertEquals($system, $permissionProfile->getSystem());
        static::assertEquals($default, $permissionProfile->getDefault());
        static::assertEquals($scope, $permissionProfile->getScope());
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

        static::assertEquals($permissions, $permissionProfile->getPermissions());

        static::assertTrue($permissionProfile->containsPermission(Permission::ACCESS_DASHBOARD));
        static::assertFalse($permissionProfile->containsPermission(Permission::ACCESS_ADMIN_USERS));

        static::assertTrue($permissionProfile->containsAllPermissions($permissions));

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

        static::assertFalse($permissionProfile->containsAllPermissions($morePermissions));
        static::assertTrue($permissionProfile->containsAllPermissions($fewerPermissions));
        static::assertTrue($permissionProfile->containsAnyPermission($fewerPermissions));
        static::assertTrue($permissionProfile->containsAnyPermission($morePermissions));
        static::assertFalse($permissionProfile->containsAnyPermission($notPermissions));

        $newPermissions = [
            Permission::ACCESS_DASHBOARD,
            Permission::ACCESS_MULTIMEDIA_SERIES,
            Permission::ACCESS_LIVE_CHANNELS,
            Permission::ACCESS_LIVE_EVENTS,
            'ROLE_ACCESS_IMPORTER',
            Permission::ACCESS_ADMIN_USERS,
        ];

        static::assertEquals($newPermissions, $permissionProfile->addPermission(Permission::ACCESS_ADMIN_USERS));
        static::assertTrue($permissionProfile->containsPermission(Permission::ACCESS_ADMIN_USERS));

        static::assertTrue($permissionProfile->removePermission(Permission::ACCESS_DASHBOARD));
        static::assertFalse($permissionProfile->containsPermission(Permission::ACCESS_DASHBOARD));

        static::assertFalse($permissionProfile->removePermission(Permission::ACCESS_WIZARD_UPLOAD));
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

        static::assertTrue($permissionProfile->isGlobal());
        static::assertFalse($permissionProfile->isPersonal());
        static::assertFalse($permissionProfile->isNone());
    }

    public function testIsDefault()
    {
        $name = 'User Test Permission';
        $default = true;

        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);
        $permissionProfile->setDefault($default);

        static::assertTrue($permissionProfile->isDefault());

        $name = 'User Test Permission 2';
        $default = false;

        $permissionProfile2 = new PermissionProfile();

        $permissionProfile2->setName($name);
        $permissionProfile2->setDefault($default);

        static::assertFalse($permissionProfile2->isDefault());
    }

    public function testIsSystem()
    {
        $name = 'User Test Permission';
        $system = true;

        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);
        $permissionProfile->setSystem($system);

        static::assertTrue($permissionProfile->isSystem());

        $name = 'User Test Permission 2';
        $system = false;

        $permissionProfile2 = new PermissionProfile();

        $permissionProfile2->setName($name);
        $permissionProfile2->setSystem($system);

        static::assertFalse($permissionProfile2->isSystem());
    }

    public function testToString()
    {
        $name = 'User Test Permission';

        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);

        static::assertEquals($name, $permissionProfile->__toString());
    }
}
