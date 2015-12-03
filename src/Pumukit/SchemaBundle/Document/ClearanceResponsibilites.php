<?php

namespace Pumukit\SchemaBundle\Document;

class ClearanceResponsibilities
{
    const ACCESS_DASHBOARD = 1;
    const ACCESS_MULTIMEDIA_SERIES= 2;
    const ACCESS_LIVE_CHANNELS = 3;
    const ACCESS_LIVE_EVENTS = 4;
    const ACCESS_JOBS = 5;
    const ACCESS_PEOPLE = 6;
    const ACCESS_TAGS = 7;
    const ACCESS_BROADCASTS = 8;
    const ACCESS_SERIES_TYPES = 9;
    const ACCESS_ADMIN_USERS = 10;
    const ACCESS_ROLES = 11;
    const ACCESS_INGESTOR = 12;
    const ACCESS_OPENCAST_INGESTOR = 13;
    const CHANGE_MULTIMEDIA_STATUS = 14;
    const ACCESS_PUBLICATION_TAB = 15;
    const UPLOAD_WITHOUT_WIZARD = 16;

    public static $clearanceDescription = array(
                                                1 => "Access Dashboard",
                                                2 => "Access Multimedia Series",
                                                3 => "Access Live Channels",
                                                4 => "Access Live Events",
                                                5 => "Access Jobs",
                                                6 => "Access People",
                                                7 => "Access Tags",
                                                8 => "Access Broadcasts",
                                                9 => "Access Series Types",
                                                10 => "Access Admin Users",
                                                11 => "Access Roles",
                                                12 => "Access Ingestor",
                                                13 => "Access Opencast Ingestor",
                                                14 => "Change Multimedia Status",
                                                15 => "Access Publication Tab",
                                                16 => "Upload Without Wizard"
                                                );
}