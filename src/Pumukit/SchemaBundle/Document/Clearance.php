<?php

namespace Pumukit\SchemaBundle\Document;

class Clearance
{
    const ACCESS_DASHBOARD = 'ACCESS_DASHBOARD';
    const ACCESS_MULTIMEDIA_SERIES = 'ACCESS_MULTIMEDIA_SERIES';
    const ACCESS_LIVE_CHANNELS = 'ACCESS_LIVE_CHANNELS';
    const ACCESS_LIVE_EVENTS = 'ACCESS_LIVE_EVENTS';
    const ACCESS_JOBS = 'ACCESS_JOBS';
    const ACCESS_PEOPLE = 'ACCESS_PEOPLE';
    const ACCESS_TAGS = 'ACCESS_TAGS';
    const ACCESS_BROADCASTS = 'ACCESS_BROADCASTS';
    const ACCESS_SERIES_TYPES = 'ACCESS_SERIES_TYPES';
    const ACCESS_ADMIN_USERS = 'ACCESS_ADMIN_USERS';
    const ACCESS_ROLES = 'ACCESS_ROLES';
    const ACCESS_USER_ACCESS_LEVEL = 'ACCESS_USER_ACCESS_LEVEL';
    const ACCESS_INGESTOR = 'ACCESS_INGESTOR';
    const CHANGE_MULTIMEDIA_STATUS = 'CHANGE_MULTIMEDIA_STATUS';
    const ACCESS_PUBLICATION_TAB = 'ACCESS_PUBLICATION_TAB';
    const UPLOAD_WITHOUT_WIZARD = 'UPLOAD_WITHOUT_WIZARD';

    public static $clearanceDescription = array(
                                                Clearance::ACCESS_DASHBOARD => "Access Dashboard",
                                                Clearance::ACCESS_MULTIMEDIA_SERIES => "Access Multimedia Series",
                                                Clearance::ACCESS_LIVE_CHANNELS => "Access Live Channels",
                                                Clearance::ACCESS_LIVE_EVENTS => "Access Live Events",
                                                Clearance::ACCESS_JOBS => "Access Jobs",
                                                Clearance::ACCESS_PEOPLE => "Access People",
                                                Clearance::ACCESS_TAGS => "Access Tags",
                                                Clearance::ACCESS_BROADCASTS => "Access Broadcasts",
                                                Clearance::ACCESS_SERIES_TYPES => "Access Series Types",
                                                Clearance::ACCESS_ADMIN_USERS => "Access Admin Users",
                                                Clearance::ACCESS_ROLES => "Access Roles",
                                                Clearance::ACCESS_USER_ACCESS_LEVEL => "Access Users Access Level",
                                                Clearance::ACCESS_INGESTOR => "Access Ingestor",
                                                Clearance::CHANGE_MULTIMEDIA_STATUS => "Change Multimedia Status",
                                                Clearance::ACCESS_PUBLICATION_TAB => "Access Publication Tab",
                                                Clearance::UPLOAD_WITHOUT_WIZARD => "Upload Without Wizard"
                                                );
}