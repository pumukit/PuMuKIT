<?php

namespace Pumukit\SchemaBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;

/**
 * @internal
 * @coversNothing
 */
class PermissionTest extends TestCase
{
    public function testStaticConstants()
    {
        $this->assertTrue(array_key_exists(Permission::ACCESS_DASHBOARD, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_MULTIMEDIA_SERIES, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_LIVE_CHANNELS, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_LIVE_EVENTS, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_JOBS, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_PEOPLE, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_TAGS, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_BROADCASTS, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_SERIES_TYPES, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_ADMIN_USERS, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_GROUPS, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_ROLES, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_PERMISSION_PROFILES, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::CHANGE_MMOBJECT_STATUS, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::CHANGE_MMOBJECT_PUBCHANNEL, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_PUBLICATION_TAB, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_WIZARD_UPLOAD, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_ADVANCED_UPLOAD, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_API, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_INBOX, Permission::$permissionDescription));

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
        $accessBroadcasts = [
            'description' => 'Access Broadcasts',
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

        $this->assertEquals($accessDashboard, Permission::$permissionDescription[Permission::ACCESS_DASHBOARD]);
        $this->assertEquals($accessMultimediaSeries, Permission::$permissionDescription[Permission::ACCESS_MULTIMEDIA_SERIES]);
        $this->assertEquals($accessLiveChannels, Permission::$permissionDescription[Permission::ACCESS_LIVE_CHANNELS]);
        $this->assertEquals($accessLiveEvents, Permission::$permissionDescription[Permission::ACCESS_LIVE_EVENTS]);
        $this->assertEquals($accessJobs, Permission::$permissionDescription[Permission::ACCESS_JOBS]);
        $this->assertEquals($accessPeople, Permission::$permissionDescription[Permission::ACCESS_PEOPLE]);
        $this->assertEquals($accessTags, Permission::$permissionDescription[Permission::ACCESS_TAGS]);
        $this->assertEquals($accessBroadcasts, Permission::$permissionDescription[Permission::ACCESS_BROADCASTS]);
        $this->assertEquals($accessSeriesTypes, Permission::$permissionDescription[Permission::ACCESS_SERIES_TYPES]);
        $this->assertEquals($accessAdminUsers, Permission::$permissionDescription[Permission::ACCESS_ADMIN_USERS]);
        $this->assertEquals($accessGroups, Permission::$permissionDescription[Permission::ACCESS_GROUPS]);
        $this->assertEquals($accessRoles, Permission::$permissionDescription[Permission::ACCESS_ROLES]);
        $this->assertEquals($accessPermissionProfiles, Permission::$permissionDescription[Permission::ACCESS_PERMISSION_PROFILES]);
        $this->assertEquals($changeMmObjectStatus, Permission::$permissionDescription[Permission::CHANGE_MMOBJECT_STATUS]);
        $this->assertEquals($changeMmObjectPubChannel, Permission::$permissionDescription[Permission::CHANGE_MMOBJECT_PUBCHANNEL]);
        $this->assertEquals($accessPublicationTab, Permission::$permissionDescription[Permission::ACCESS_PUBLICATION_TAB]);
        $this->assertEquals($accessWizardUpload, Permission::$permissionDescription[Permission::ACCESS_WIZARD_UPLOAD]);
        $this->assertEquals($accessAdvancedUpload, Permission::$permissionDescription[Permission::ACCESS_ADVANCED_UPLOAD]);
        $this->assertEquals($accessApi, Permission::$permissionDescription[Permission::ACCESS_API]);
        $this->assertEquals($accessInbox, Permission::$permissionDescription[Permission::ACCESS_INBOX]);
        $this->assertEquals($modifyOwner, Permission::$permissionDescription[Permission::MODIFY_OWNER]);
    }
}
