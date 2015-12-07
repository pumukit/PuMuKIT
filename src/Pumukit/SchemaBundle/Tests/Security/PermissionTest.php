<?php

namespace Pumukit\SchemaBundle\Tests\Security;

use Pumukit\SchemaBundle\Security\Permission;

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
        $this->assertTrue(array_key_exists(Permission::ACCESS_ROLES, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_PERMISSION_PROFILES, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_INGESTOR, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::CHANGE_MMOBJECT_STATUS, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::CHANGE_MMOBJECT_PUBCHANNEL, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_PUBLICATION_TAB, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_WIZARD_UPLOAD, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_ADVANCED_UPLOAD, Permission::$permissionDescription));
        $this->assertTrue(array_key_exists(Permission::ACCESS_API, Permission::$permissionDescription));

        $accessDashboard = "Access Dashboard";
        $accessMultimediaSeries = "Access Multimedia Series";
        $accessLiveChannels = "Access Live Channels";
        $accessLiveEvents = "Access Live Events";
        $accessJobs = "Access Jobs";
        $accessPeople = "Access People";
        $accessTags = "Access Tags";
        $accessBroadcasts = "Access Broadcasts";
        $accessSeriesTypes = "Access Series Types";
        $accessAdminUsers = "Access Admin Users";
        $accessRoles = "Access Roles";
        $accessPermissionProfiles = "Access Permission Profiles";
        $accessIngestor = "Access Ingestor";
        $changeMmObjectStatus = "Change Multimedia Object Status";
        $changeMmObjectPubChannel = "Change Multimedia Object Publication Channel";
        $accessPublicationTab = "Access Publication Tab";
        $accessWizardUpload = "Access Wizard Upload";
        $accessAdvancedUpload = "Access Advanced Upload";
        $accessApi = "Access API";

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
        $this->assertEquals($accessRoles, Permission::$permissionDescription[Permission::ACCESS_ROLES]);
        $this->assertEquals($accessPermissionProfiles, Permission::$permissionDescription[Permission::ACCESS_PERMISSION_PROFILES]);
        $this->assertEquals($accessIngestor, Permission::$permissionDescription[Permission::ACCESS_INGESTOR]);
        $this->assertEquals($changeMmObjectStatus, Permission::$permissionDescription[Permission::CHANGE_MMOBJECT_STATUS]);
        $this->assertEquals($changeMmObjectPubChannel, Permission::$permissionDescription[Permission::CHANGE_MMOBJECT_PUBCHANNEL]);
        $this->assertEquals($accessPublicationTab, Permission::$permissionDescription[Permission::ACCESS_PUBLICATION_TAB]);
        $this->assertEquals($accessWizardUpload, Permission::$permissionDescription[Permission::ACCESS_WIZARD_UPLOAD]);
        $this->assertEquals($accessAdvancedUpload, Permission::$permissionDescription[Permission::ACCESS_ADVANCED_UPLOAD]);
        $this->assertEquals($accessApi, Permission::$permissionDescription[Permission::ACCESS_API]);
    }
}