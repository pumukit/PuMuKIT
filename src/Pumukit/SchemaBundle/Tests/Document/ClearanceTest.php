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
        $this->assertTrue(array_key_exists(Clearance::ACCESS_USER_ACCESS_LEVEL, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_INGESTOR, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::CHANGE_MULTIMEDIA_STATUS, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::ACCESS_PUBLICATION_TAB, Clearance::$clearanceDescription));
        $this->assertTrue(array_key_exists(Clearance::UPLOAD_WITHOUT_WIZARD, Clearance::$clearanceDescription));

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
        $accessUserAccessLevel = "Access Users Access Level";
        $accessIngestor = "Access Ingestor";
        $changeMultimediaStatus = "Change Multimedia Status";
        $accessPublicationTab = "Access Publication Tab";
        $uploadWithoutWizard = "Upload Without Wizard";

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
        $this->assertEquals($accessUserAccessLevel, Clearance::$clearanceDescription[Clearance::ACCESS_USER_ACCESS_LEVEL]);
        $this->assertEquals($accessIngestor, Clearance::$clearanceDescription[Clearance::ACCESS_INGESTOR]);
        $this->assertEquals($changeMultimediaStatus, Clearance::$clearanceDescription[Clearance::CHANGE_MULTIMEDIA_STATUS]);
        $this->assertEquals($accessPublicationTab, Clearance::$clearanceDescription[Clearance::ACCESS_PUBLICATION_TAB]);
        $this->assertEquals($uploadWithoutWizard, Clearance::$clearanceDescription[Clearance::UPLOAD_WITHOUT_WIZARD]);
    }
}