<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Clearance;

class ClearanceTest extends \PHPUnit_Framework_TestCase
{
    public function testStaticConstants()
    {
        $this->assertTrue(array_key_exists(Clearance::ACCESS_DASHBOARD, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_MULTIMEDIA_SERIES, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_LIVE_CHANNELS, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_LIVE_EVENTS, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_JOBS, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_PEOPLE, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_TAGS, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_BROADCASTS, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_SERIES_TYPES, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_ADMIN_USERS, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_ROLES, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_USER_CLEARANCE_LEVEL, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_INGESTOR, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::CHANGE_MMOBJECT_STATUS, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::CHANGE_MMOBJECT_PUBCHANNEL, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_PUBLICATION_TAB, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_WIZARD_UPLOAD, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_ADVANCED_UPLOAD, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_API, Clearance::$clearanceDescription));

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
        $accessUserClearanceLevel = "Access Users Clearance Level";
        $accessIngestor = "Access Ingestor";
        $changeMmObjectStatus = "Change Multimedia Object Status";
        $changeMmObjectPubChannel = "Change Multimedia Object Publication Channel";
        $accessPublicationTab = "Access Publication Tab";
        $accessWizardUpload = "Access Wizard Upload";
        $accessAdvancedUpload = "Access Advanced Upload";
        $accessApi = "Access API";

        $this->assertEquals($accessDashboard, Clearance::$clearanceDescription[Clearance::ACCESS_DASHBOARD]);
        $this->assertEquals($accessMultimediaSeries, Clearance::$clearanceDescription[Clearance::ACCESS_MULTIMEDIA_SERIES]);
        $this->assertEquals($accessLiveChannels, Clearance::$clearanceDescription[Clearance::ACCESS_LIVE_CHANNELS]);
        $this->assertEquals($accessLiveEvents, Clearance::$clearanceDescription[Clearance::ACCESS_LIVE_EVENTS]);
        $this->assertEquals($accessJobs, Clearance::$clearanceDescription[Clearance::ACCESS_JOBS]);
        $this->assertEquals($accessPeople, Clearance::$clearanceDescription[Clearance::ACCESS_PEOPLE]);
        $this->assertEquals($accessTags, Clearance::$clearanceDescription[Clearance::ACCESS_TAGS]);
        $this->assertEquals($accessBroadcasts, Clearance::$clearanceDescription[Clearance::ACCESS_BROADCASTS]);
        $this->assertEquals($accessSeriesTypes, Clearance::$clearanceDescription[Clearance::ACCESS_SERIES_TYPES]);
        $this->assertEquals($accessAdminUsers, Clearance::$clearanceDescription[Clearance::ACCESS_ADMIN_USERS]);
        $this->assertEquals($accessRoles, Clearance::$clearanceDescription[Clearance::ACCESS_ROLES]);
        $this->assertEquals($accessUserClearanceLevel, Clearance::$clearanceDescription[Clearance::ACCESS_USER_CLEARANCE_LEVEL]);
        $this->assertEquals($accessIngestor, Clearance::$clearanceDescription[Clearance::ACCESS_INGESTOR]);
        $this->assertEquals($changeMmObjectStatus, Clearance::$clearanceDescription[Clearance::CHANGE_MMOBJECT_STATUS]);
        $this->assertEquals($changeMmObjectPubChannel, Clearance::$clearanceDescription[Clearance::CHANGE_MMOBJECT_PUBCHANNEL]);
        $this->assertEquals($accessPublicationTab, Clearance::$clearanceDescription[Clearance::ACCESS_PUBLICATION_TAB]);
        $this->assertEquals($accessWizardUpload, Clearance::$clearanceDescription[Clearance::ACCESS_WIZARD_UPLOAD]);
        $this->assertEquals($accessAdvancedUpload, Clearance::$clearanceDescription[Clearance::ACCESS_ADVANCED_UPLOAD]);
        $this->assertEquals($accessApi, Clearance::$clearanceDescription[Clearance::ACCESS_API]);
    }
}