<?php

namespace Pumukit\SchemaBundle\Security;

class Permission
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
    const ACCESS_PERMISSION_PROFILES = 'ACCESS_PERMISSION_PROFILES';
    const ACCESS_ROLES = 'ACCESS_ROLES';
    const ACCESS_INGESTOR = 'ACCESS_INGESTOR';
    const CHANGE_MMOBJECT_STATUS = 'CHANGE_MMOBJECT_STATUS';
    const CHANGE_MMOBJECT_PUBCHANNEL = 'CHANGE_MMOBJECT_PUBCHANNEL';
    const ACCESS_PUBLICATION_TAB = 'ACCESS_PUBLICATION_TAB';
    const ACCESS_ADVANCED_UPLOAD = 'ACCESS_ADVANCED_UPLOAD';
    const ACCESS_WIZARD_UPLOAD = 'ACCESS_WIZARD_UPLOAD';
    const ACCESS_API = 'ACCESS_API';

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