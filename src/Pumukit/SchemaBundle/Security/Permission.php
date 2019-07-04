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
    const SHOW_PEOPLE_MENU = 'ROLE_SHOW_PEOPLE_MENU';
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
    const ADD_OWNER = 'ROLE_ADD_OWNER';
    const INIT_STATUS_PUBLISHED = 'ROLE_INIT_STATUS_PUBLISHED';
    const INIT_STATUS_HIDDEN = 'ROLE_INIT_STATUS_HIDDEN';
    const SHOW_CODES = 'ROLE_SHOW_CODES';
    const ROLE_SEND_NOTIFICATION_COMPLETE = 'ROLE_SEND_NOTIFICATION_COMPLETE';
    const ROLE_SEND_NOTIFICATION_ERRORS = 'ROLE_SEND_NOTIFICATION_ERRORS';
    const ACCESS_SERIES_STYLE = 'ROLE_ACCESS_SERIES_STYLE';
    const DISABLED_TRACK_PROFILES = 'ROLE_DISABLED_WIZARD_TRACK_PROFILES';
    const DISABLED_TRACK_PRIORITY = 'ROLE_DISABLED_WIZARD_TRACK_PRIORITY';

    const PREFIX_ROLE_TAG_DEFAULT = 'ROLE_TAG_DEFAULT_';

    const PREFIX_ROLE_TAG_DISABLE = 'ROLE_TAG_DISABLE_';

    public static $permissionDescription = [
        self::ACCESS_DASHBOARD => [
            'description' => 'Access Dashboard',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_SERIES => [
            'description' => 'Access Media Manager',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_LIVE_CHANNELS => [
            'description' => 'Access Live Channels',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_LIVE_EVENTS => [
            'description' => 'Access Live Events',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_JOBS => [
            'description' => 'Access Jobs',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_PEOPLE => [
            'description' => 'Access People',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::SHOW_PEOPLE_MENU => [
            'description' => 'Show People Menu Item',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_TAGS => [
            'description' => 'Access Tags',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_BROADCASTS => [
            'description' => 'Access Broadcasts',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_TYPES => [
            'description' => 'Access Series Types',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_ADMIN_USERS => [
            'description' => 'Access Admin Users',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_GROUPS => [
            'description' => 'Access Groups',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_PERMISSION_PROFILES => [
            'description' => 'Access Permission Profiles',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_ROLES => [
            'description' => 'Access Roles',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::CHANGE_MMOBJECT_STATUS => [
            'description' => 'Change Multimedia Object Status',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::CHANGE_MMOBJECT_PUBCHANNEL => [
            'description' => 'Change Multimedia Object Publication Channel',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_PUBLICATION_TAB => [
            'description' => 'Access Publication Tab',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_ADVANCED_UPLOAD => [
            'description' => 'Access Advanced Upload',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_EDIT_PLAYLIST => [
            'description' => 'Access Edit Playlist',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_WIZARD_UPLOAD => [
            'description' => 'Access Wizard Upload',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::SHOW_WIZARD_MENU => [
            'description' => 'Show Wizard Menu Item',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_API => [
            'description' => 'Access API',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_INBOX => [
            'description' => 'Access Inbox',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::MODIFY_OWNER => [
            'description' => 'Modify Owners & Groups',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ADD_OWNER => [
            'description' => 'Add Owners',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::SHOW_CODES => [
            'description' => 'Show tag and group codes in the backoffice',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ROLE_SEND_NOTIFICATION_ERRORS => [
            'description' => 'Receive failed job notifications',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ROLE_SEND_NOTIFICATION_COMPLETE => [
            'description' => 'Receive completed broadcast job notifications',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::INIT_STATUS_PUBLISHED => [
            'description' => 'Init Multimedia Objects in published status',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::INIT_STATUS_HIDDEN => [
            'description' => 'Init Multimedia Objects in hidden status',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_STYLE => [
            'description' => 'Access Series Styles',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::DISABLED_TRACK_PRIORITY => [
            'description' => 'Disabled track priority on wizard',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::DISABLED_TRACK_PROFILES => [
            'description' => 'Disabled track profiles on wizard',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
    ];

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
