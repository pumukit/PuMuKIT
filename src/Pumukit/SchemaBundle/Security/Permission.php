<?php

namespace Pumukit\SchemaBundle\Security;

class Permission
{
    const ACCESS_DASHBOARD = 'ROLE_ACCESS_DASHBOARD';
    const ACCESS_MULTIMEDIA_SERIES = 'ROLE_ACCESS_MULTIMEDIA_SERIES';
    const ACCESS_LIVE_CHANNELS = 'ROLE_ACCESS_LIVE_CHANNELS';
    const ACCESS_LIVE_EVENTS = 'ROLE_ACCESS_LIVE_EVENTS';
    const ACCESS_JOBS = 'ROLE_ACCESS_JOBS';
    const ACCESS_PEOPLE = 'ROLE_ACCESS_PEOPLE';
    const ACCESS_TAGS = 'ROLE_ACCESS_TAGS';
    const ACCESS_BROADCASTS = 'ROLE_ACCESS_BROADCASTS';
    const ACCESS_SERIES_TYPES = 'ROLE_ACCESS_SERIES_TYPES';
    const ACCESS_ADMIN_USERS = 'ROLE_ACCESS_ADMIN_USERS';
    const ACCESS_PERMISSION_PROFILES = 'ROLE_ACCESS_PERMISSION_PROFILES';
    const ACCESS_ROLES = 'ROLE_ACCESS_ROLES';
    const ACCESS_INGESTOR = 'ROLE_ACCESS_INGESTOR';
    const CHANGE_MMOBJECT_STATUS = 'ROLE_CHANGE_MMOBJECT_STATUS';
    const CHANGE_MMOBJECT_PUBCHANNEL = 'ROLE_CHANGE_MMOBJECT_PUBCHANNEL';
    const ACCESS_PUBLICATION_TAB = 'ROLE_ACCESS_PUBLICATION_TAB';
    const ACCESS_ADVANCED_UPLOAD = 'ROLE_ACCESS_ADVANCED_UPLOAD';
    const ACCESS_WIZARD_UPLOAD = 'ROLE_ACCESS_WIZARD_UPLOAD';
    const ACCESS_API = 'ROLE_ACCESS_API';

    public static $permissionDescription = array(
                                                Permission::ACCESS_DASHBOARD => "Access Dashboard",
                                                Permission::ACCESS_MULTIMEDIA_SERIES => "Access Multimedia Series",
                                                Permission::ACCESS_LIVE_CHANNELS => "Access Live Channels",
                                                Permission::ACCESS_LIVE_EVENTS => "Access Live Events",
                                                Permission::ACCESS_JOBS => "Access Jobs",
                                                Permission::ACCESS_PEOPLE => "Access People",
                                                Permission::ACCESS_TAGS => "Access Tags",
                                                Permission::ACCESS_BROADCASTS => "Access Broadcasts",
                                                Permission::ACCESS_SERIES_TYPES => "Access Series Types",
                                                Permission::ACCESS_ADMIN_USERS => "Access Admin Users",
                                                Permission::ACCESS_PERMISSION_PROFILES => "Access Permission Profiles",
                                                Permission::ACCESS_ROLES => "Access Roles",
                                                Permission::ACCESS_INGESTOR => "Access Ingestor",
                                                Permission::CHANGE_MMOBJECT_STATUS => "Change Multimedia Object Status",
                                                Permission::CHANGE_MMOBJECT_PUBCHANNEL => "Change Multimedia Object Publication Channel",
                                                Permission::ACCESS_PUBLICATION_TAB => "Access Publication Tab",
                                                Permission::ACCESS_ADVANCED_UPLOAD => "Access Advanced Upload",
                                                Permission::ACCESS_WIZARD_UPLOAD => "Access Wizard Upload",
                                                Permission::ACCESS_API => "Access API"
                                                );
}