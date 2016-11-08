<?php

namespace Pumukit\SchemaBundle\Tests\Security;

use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Document\PermissionProfile;

class PermissionTest extends \PHPUnit_Framework_TestCase
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

        $accessDashboard = array(
            'description' => 'Access Dashboard',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessMultimediaSeries = array(
            'description' => 'Access Media Manager',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessLiveChannels = array(
            'description' => 'Access Live Channels',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessLiveEvents = array(
            'description' => 'Access Live Events',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessJobs = array(
            'description' => 'Access Jobs',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessPeople = array(
            'description' => 'Access People',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessTags = array(
            'description' => 'Access Tags',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessBroadcasts = array(
            'description' => 'Access Broadcasts',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessSeriesTypes = array(
            'description' => 'Access Series Types',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessAdminUsers = array(
            'description' => 'Access Admin Users',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessGroups = array(
            'description' => 'Access Groups',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessPermissionProfiles = array(
            'description' => 'Access Permission Profiles',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessRoles = array(
            'description' => 'Access Roles',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessImporter = array(
            'description' => 'Access Importer',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $changeMmObjectStatus = array(
            'description' => 'Change Multimedia Object Status',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $changeMmObjectPubChannel = array(
            'description' => 'Change Multimedia Object Publication Channel',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessPublicationTab = array(
            'description' => 'Access Publication Tab',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessAdvancedUpload = array(
            'description' => 'Access Advanced Upload',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessWizardUpload = array(
            'description' => 'Access Wizard Upload',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessApi = array(
            'description' => 'Access API',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $accessInbox = array(
            'description' => 'Access Inbox',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );
        $modifyOwner = array(
            'description' => 'Modify Owners & Groups',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        );

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
