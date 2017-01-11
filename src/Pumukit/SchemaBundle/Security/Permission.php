<?php

namespace Pumukit\SchemaBundle\Security;

use Pumukit\SchemaBundle\Document\PermissionProfile;

class Permission
{
    const ACCESS_DASHBOARD = 'ROLE_ACCESS_DASHBOARD';
    const ACCESS_MULTIMEDIA_SERIES = 'ROLE_ACCESS_MULTIMEDIA_SERIES';
    const ACCESS_LIVE_CHANNELS = 'ROLE_ACCESS_LIVE_CHANNELS';
    const ACCESS_LIVE_EVENTS = 'ROLE_ACCESS_LIVE_EVENTS';
    const ACCESS_JOBS = 'ROLE_ACCESS_JOBS';
    const ACCESS_PEOPLE = 'ROLE_ACCESS_PEOPLE';
    const ACCESS_TAGS = 'ROLE_ACCESS_TAGS';
    // @deprecated in version 2.3
    const ACCESS_BROADCASTS = 'ROLE_ACCESS_BROADCASTS';
    const ACCESS_SERIES_TYPES = 'ROLE_ACCESS_SERIES_TYPES';
    const ACCESS_ADMIN_USERS = 'ROLE_ACCESS_ADMIN_USERS';
    const ACCESS_PERMISSION_PROFILES = 'ROLE_ACCESS_PERMISSION_PROFILES';
    const ACCESS_ROLES = 'ROLE_ACCESS_ROLES';
    const ACCESS_GROUPS = 'ROLE_ACCESS_GROUPS';
    const CHANGE_MMOBJECT_STATUS = 'ROLE_CHANGE_MMOBJECT_STATUS';
    const CHANGE_MMOBJECT_PUBCHANNEL = 'ROLE_CHANGE_MMOBJECT_PUBCHANNEL';
    const ACCESS_PUBLICATION_TAB = 'ROLE_ACCESS_PUBLICATION_TAB';
    const ACCESS_ADVANCED_UPLOAD = 'ROLE_ACCESS_ADVANCED_UPLOAD';
    const ACCESS_EDIT_PLAYLIST = 'ROLE_ACCESS_EDIT_PLAYLIST';
    const ACCESS_WIZARD_UPLOAD = 'ROLE_ACCESS_WIZARD_UPLOAD';
    const SHOW_WIZARD_MENU = 'ROLE_SHOW_WIZARD_MENU';
    const ACCESS_API = 'ROLE_ACCESS_API';
    const ACCESS_INBOX = 'ROLE_ACCESS_INBOX';
    const MODIFY_OWNER = 'ROLE_MODIFY_OWNER';
    const INIT_STATUS_PUBLISHED = 'ROLE_INIT_STATUS_PUBLISHED';

    public static $permissionDescription = array(
        self::ACCESS_DASHBOARD => array(
            'description' => 'Access Dashboard',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_MULTIMEDIA_SERIES => array(
            'description' => 'Access Media Manager',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_LIVE_CHANNELS => array(
            'description' => 'Access Live Channels',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_LIVE_EVENTS => array(
            'description' => 'Access Live Events',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_JOBS => array(
            'description' => 'Access Jobs',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_PEOPLE => array(
            'description' => 'Access People',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_TAGS => array(
            'description' => 'Access Tags',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_BROADCASTS => array(
            'description' => 'Access Broadcasts',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_SERIES_TYPES => array(
            'description' => 'Access Series Types',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_ADMIN_USERS => array(
            'description' => 'Access Admin Users',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_GROUPS => array(
            'description' => 'Access Groups',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_PERMISSION_PROFILES => array(
            'description' => 'Access Permission Profiles',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_ROLES => array(
            'description' => 'Access Roles',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::CHANGE_MMOBJECT_STATUS => array(
            'description' => 'Change Multimedia Object Status',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::CHANGE_MMOBJECT_PUBCHANNEL => array(
            'description' => 'Change Multimedia Object Publication Channel',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_PUBLICATION_TAB => array(
            'description' => 'Access Publication Tab',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_ADVANCED_UPLOAD => array(
            'description' => 'Access Advanced Upload',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_EDIT_PLAYLIST => array(
            'description' => 'Access Edit Playlist',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_WIZARD_UPLOAD => array(
            'description' => 'Access Wizard Upload',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::SHOW_WIZARD_MENU => array(
            'description' => 'Show Wizard Menu Item',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_API => array(
            'description' => 'Access API',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::ACCESS_INBOX => array(
            'description' => 'Access Inbox',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::MODIFY_OWNER => array(
            'description' => 'Modify Owners & Groups',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
        self::INIT_STATUS_PUBLISHED => array(
            'description' => 'Init Multimedia Objects in published status',
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array(),
            ),
        ),
    );

    const PREFIX_ROLE_TAG_DEFAULT = 'ROLE_TAG_DEFAULT_';

    public static function isRoleTagDefault($role)
    {
        return 0 === strpos($role, self::PREFIX_ROLE_TAG_DEFAULT);
    }

    public static function getPubChannelForRoleTagDefault($role)
    {
        if (self::isRoleTagDefault($role)) {
            return substr($role, strlen(self::PREFIX_ROLE_TAG_DEFAULT));
        }

        return false;
    }

    public static function getRoleTagDefaultForPubChannel($cod)
    {
        return self::PREFIX_ROLE_TAG_DEFAULT.strtoupper($cod);
    }

    const PREFIX_ROLE_TAG_DISABLE = 'ROLE_TAG_DISABLE_';

    public static function isRoleTagDisable($role)
    {
        return 0 === strpos($role, self::PREFIX_ROLE_TAG_DISABLE);
    }

    public static function getPubChannelForRoleTagDisable($role)
    {
        if (self::isRoleTagDisable($role)) {
            return substr($role, strlen(self::PREFIX_ROLE_TAG_DISABLE));
        }

        return false;
    }

    public static function getRoleTagDisableForPubChannel($cod)
    {
        return self::PREFIX_ROLE_TAG_DISABLE.strtoupper($cod);
    }
}
