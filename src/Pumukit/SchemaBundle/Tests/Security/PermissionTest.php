<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;

/**
 * @internal
 *
 * @coversNothing
 */
class PermissionTest extends TestCase
{
    public function testStaticConstants()
    {
        static::assertTrue(array_key_exists(Permission::ACCESS_DASHBOARD, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_MULTIMEDIA_SERIES, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_LIVE_CHANNELS, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_LIVE_EVENTS, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_JOBS, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_PEOPLE, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_TAGS, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_SERIES_TYPES, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_ADMIN_USERS, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_GROUPS, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_ROLES, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_PERMISSION_PROFILES, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::CHANGE_MMOBJECT_STATUS, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::CHANGE_MMOBJECT_PUBCHANNEL, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_PUBLICATION_TAB, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_WIZARD_UPLOAD, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_ADVANCED_UPLOAD, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_API, Permission::$permissionDescription));
        static::assertTrue(array_key_exists(Permission::ACCESS_INBOX, Permission::$permissionDescription));

        $accessDashboard = [
            'description' => 'Access Dashboard',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessMultimediaSeries = [
            'description' => 'Access Media Manager',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessLiveChannels = [
            'description' => 'Access Live Channels',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessLiveEvents = [
            'description' => 'Access Live Events',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessJobs = [
            'description' => 'Access Jobs',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessPeople = [
            'description' => 'Access People',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessTags = [
            'description' => 'Access Tags',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessSeriesTypes = [
            'description' => 'Access Series Types',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessAdminUsers = [
            'description' => 'Access Admin Users',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessGroups = [
            'description' => 'Access Groups',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessPermissionProfiles = [
            'description' => 'Access Permission Profiles',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessRoles = [
            'description' => 'Access Roles',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessImporter = [
            'description' => 'Access Importer',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $changeMmObjectStatus = [
            'description' => 'Change Multimedia Object Status',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $changeMmObjectPubChannel = [
            'description' => 'Change Multimedia Object Publication Channel',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessPublicationTab = [
            'description' => 'Access Publication Tab',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessAdvancedUpload = [
            'description' => 'Access Advanced Upload',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessWizardUpload = [
            'description' => 'Access Wizard Upload',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessApi = [
            'description' => 'Access API',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $accessInbox = [
            'description' => 'Access Inbox',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];
        $modifyOwner = [
            'description' => 'Modify Owners & Groups',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ];

        static::assertEquals($accessDashboard, Permission::$permissionDescription[Permission::ACCESS_DASHBOARD]);
        static::assertEquals($accessMultimediaSeries, Permission::$permissionDescription[Permission::ACCESS_MULTIMEDIA_SERIES]);
        static::assertEquals($accessLiveChannels, Permission::$permissionDescription[Permission::ACCESS_LIVE_CHANNELS]);
        static::assertEquals($accessLiveEvents, Permission::$permissionDescription[Permission::ACCESS_LIVE_EVENTS]);
        static::assertEquals($accessJobs, Permission::$permissionDescription[Permission::ACCESS_JOBS]);
        static::assertEquals($accessPeople, Permission::$permissionDescription[Permission::ACCESS_PEOPLE]);
        static::assertEquals($accessTags, Permission::$permissionDescription[Permission::ACCESS_TAGS]);
        static::assertEquals($accessSeriesTypes, Permission::$permissionDescription[Permission::ACCESS_SERIES_TYPES]);
        static::assertEquals($accessAdminUsers, Permission::$permissionDescription[Permission::ACCESS_ADMIN_USERS]);
        static::assertEquals($accessGroups, Permission::$permissionDescription[Permission::ACCESS_GROUPS]);
        static::assertEquals($accessRoles, Permission::$permissionDescription[Permission::ACCESS_ROLES]);
        static::assertEquals($accessPermissionProfiles, Permission::$permissionDescription[Permission::ACCESS_PERMISSION_PROFILES]);
        static::assertEquals($changeMmObjectStatus, Permission::$permissionDescription[Permission::CHANGE_MMOBJECT_STATUS]);
        static::assertEquals($changeMmObjectPubChannel, Permission::$permissionDescription[Permission::CHANGE_MMOBJECT_PUBCHANNEL]);
        static::assertEquals($accessPublicationTab, Permission::$permissionDescription[Permission::ACCESS_PUBLICATION_TAB]);
        static::assertEquals($accessWizardUpload, Permission::$permissionDescription[Permission::ACCESS_WIZARD_UPLOAD]);
        static::assertEquals($accessAdvancedUpload, Permission::$permissionDescription[Permission::ACCESS_ADVANCED_UPLOAD]);
        static::assertEquals($accessApi, Permission::$permissionDescription[Permission::ACCESS_API]);
        static::assertEquals($accessInbox, Permission::$permissionDescription[Permission::ACCESS_INBOX]);
        static::assertEquals($modifyOwner, Permission::$permissionDescription[Permission::MODIFY_OWNER]);
    }
}
