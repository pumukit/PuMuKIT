<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Security;

use Pumukit\SchemaBundle\Document\PermissionProfile;

class Permission
{
    public const ACCESS_DASHBOARD = 'ROLE_ACCESS_DASHBOARD';
    public const ACCESS_MULTIMEDIA_SERIES = 'ROLE_ACCESS_MULTIMEDIA_SERIES';
    public const ACCESS_LIVE_CHANNELS = 'ROLE_ACCESS_LIVE_CHANNELS';
    public const ACCESS_LIVE_EVENTS = 'ROLE_ACCESS_LIVE_EVENTS';
    public const ACCESS_JOBS = 'ROLE_ACCESS_JOBS';
    public const ACCESS_PEOPLE = 'ROLE_ACCESS_PEOPLE';
    public const SHOW_PEOPLE_MENU = 'ROLE_SHOW_PEOPLE_MENU';
    public const ACCESS_TAGS = 'ROLE_ACCESS_TAGS';
    public const ACCESS_SERIES_TYPES = 'ROLE_ACCESS_SERIES_TYPES';
    public const ACCESS_ADMIN_USERS = 'ROLE_ACCESS_ADMIN_USERS';
    public const ACCESS_PERMISSION_PROFILES = 'ROLE_ACCESS_PERMISSION_PROFILES';
    public const ACCESS_ROLES = 'ROLE_ACCESS_ROLES';
    public const ACCESS_GROUPS = 'ROLE_ACCESS_GROUPS';
    public const CHANGE_MMOBJECT_STATUS = 'ROLE_CHANGE_MMOBJECT_STATUS';
    public const CHANGE_MMOBJECT_PUBCHANNEL = 'ROLE_CHANGE_MMOBJECT_PUBCHANNEL';
    public const ACCESS_PUBLICATION_TAB = 'ROLE_ACCESS_PUBLICATION_TAB';
    public const ACCESS_ADVANCED_UPLOAD = 'ROLE_ACCESS_ADVANCED_UPLOAD';
    public const ACCESS_EDIT_PLAYLIST = 'ROLE_ACCESS_EDIT_PLAYLIST';
    public const ACCESS_WIZARD_UPLOAD = 'ROLE_ACCESS_WIZARD_UPLOAD';
    public const SHOW_WIZARD_MENU = 'ROLE_SHOW_WIZARD_MENU';
    public const ACCESS_API = 'ROLE_ACCESS_API';
    public const ACCESS_INBOX = 'ROLE_ACCESS_INBOX';
    public const MODIFY_OWNER = 'ROLE_MODIFY_OWNER';
    public const ADD_OWNER = 'ROLE_ADD_OWNER';
    public const INIT_STATUS_PUBLISHED = 'ROLE_INIT_STATUS_PUBLISHED';
    public const INIT_STATUS_HIDDEN = 'ROLE_INIT_STATUS_HIDDEN';
    public const SHOW_CODES = 'ROLE_SHOW_CODES';
    public const ROLE_SEND_NOTIFICATION_COMPLETE = 'ROLE_SEND_NOTIFICATION_COMPLETE';
    public const ROLE_SEND_NOTIFICATION_ERRORS = 'ROLE_SEND_NOTIFICATION_ERRORS';
    public const ACCESS_SERIES_STYLE = 'ROLE_ACCESS_SERIES_STYLE';
    public const DISABLED_TRACK_PROFILES = 'ROLE_DISABLED_WIZARD_TRACK_PROFILES';
    public const DISABLED_TRACK_PRIORITY = 'ROLE_DISABLED_WIZARD_TRACK_PRIORITY';
    public const ADD_EXTERNAL_PLAYER = 'ROLE_ADD_EXTERNAL_PLAYER';
    public const AUTO_CREATE_PERSONAL_SERIES = 'ROLE_AUTO_CREATE_PERSONAL_SERIES';
    public const ACCESS_HEAD_AND_TAIL_MANAGER = 'ROLE_ACCESS_HEAD_AND_TAIL_MANAGER';
    public const ADD_HEAD_AND_TAIL = 'ROLE_ADD_HEAD_AND_TAIL';

    // Permissions for series metadata inputs
    public const ACCESS_SERIES_META_LAST_ANNOUNCES = 'ROLE_ACCESS_SERIES_META_LAST_ANNOUNCES';
    public const ACCESS_SERIES_META_STYLE = 'ROLE_ACCESS_SERIES_META_STYLE';
    public const ACCESS_SERIES_META_DISPLAY = 'ROLE_ACCESS_SERIES_META_DISPLAY';
    public const ACCESS_SERIES_META_GROUP_LIST_OPTIONS = 'ROLE_ACCESS_SERIES_META_GROUP_LIST_OPTIONS';
    public const ACCESS_SERIES_META_KEYWORDS = 'ROLE_ACCESS_SERIES_META_KEYWORDS';
    public const ACCESS_SERIES_META_CHANNELS = 'ROLE_ACCESS_SERIES_META_CHANNELS';
    public const ACCESS_SERIES_META_HTML_CONFIGURATION = 'ROLE_ACCESS_SERIES_META_HTML_CONFIGURATION';
    public const ACCESS_SERIES_META_HEADLINE = 'ROLE_ACCESS_SERIES_META_HEADLINE';
    public const ACCESS_SERIES_META_TEMPLATE = 'ROLE_ACCESS_SERIES_META_TEMPLATE';
    public const ACCESS_SERIES_MAGIC_URL = 'ROLE_ACCESS_SERIES_MAGIC_URL';
    public const ACCESS_SERIES_EDIT_TEMPLATE = 'ROLE_ACCESS_SERIES_EDIT_TEMPLATE';

    // Permission for multimedia object
    public const ACCESS_MULTIMEDIA_META_COPYRIGHT = 'ROLE_ACCESS_MULTIMEDIA_META_COPYRIGHT';
    public const ACCESS_MULTIMEDIA_META_HEADLINE = 'ROLE_ACCESS_MULTIMEDIA_META_HEADLINE';
    public const ACCESS_MULTIMEDIA_META_KEYWORDS = 'ROLE_ACCESS_MULTIMEDIA_META_KEYWORDS';
    public const ACCESS_MULTIMEDIA_META_SUBSERIE = 'ROLE_ACCESS_MULTIMEDIA_META_SUBSERIE';
    public const ACCESS_MULTIMEDIA_META_LICENSE = 'ROLE_ACCESS_MULTIMEDIA_META_LICENSE';
    public const ACCESS_MULTIMEDIA_TRACKS_OPTIONS = 'ROLE_ACCESS_MULTIMEDIA_TRACKS_OPTIONS';
    public const ACCESS_MULTIMEDIA_CATEGORY_TAB = 'ROLE_ACCESS_MULTIMEDIA_CATEGORY_TAB';
    public const ACCESS_MULTIMEDIA_PEOPLE_TAB = 'ROLE_ACCESS_MULTIMEDIA_PEOPLE_TAB';
    public const ACCESS_MULTIMEDIA_OWNER_TAB = 'ROLE_ACCESS_MULTIMEDIA_OWNER_TAB';
    public const ACCESS_MULTIMEDIA_SYNC_TAB = 'ROLE_ACCESS_MULTIMEDIA_SYNC_TAB';
    public const ACCESS_MULTIMEDIA_MAGIC_URL = 'ROLE_ACCESS_MULTIMEDIA_MAGIC_URL';
    public const ACCESS_MULTIMEDIA_SHOW_WIZARD_BUTTON = 'ROLE_ACCESS_MULTIMEDIA_SHOW_WIZARD_BUTTON';
    public const ACCESS_MULTIMEDIA_SHOW_MULTIMEDIA_OBJECT_INFO_URL = 'ROLE_ACCESS_MULTIMEDIA_SHOW_MULTIMEDIA_OBJECT_INFO_URL';

    public const PREFIX_ROLE_TAG_DEFAULT = 'ROLE_TAG_DEFAULT_';
    public const PREFIX_ROLE_TAG_DISABLE = 'ROLE_TAG_DISABLE_';

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
        self::ADD_EXTERNAL_PLAYER => [
            'description' => 'Add an external player (iframe) into a multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::AUTO_CREATE_PERSONAL_SERIES => [
            'description' => 'Auto create personal series for the user',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_HEAD_AND_TAIL_MANAGER => [
            'description' => 'Allow access to head and tail video manager',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ADD_HEAD_AND_TAIL => [
            'description' => 'Allow add head and tail video on multimedia objects and series',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_META_LAST_ANNOUNCES => [
            'description' => 'Show last announces on series metadata',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_META_STYLE => [
            'description' => 'Show series style on series metadata',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_META_DISPLAY => [
            'description' => 'Show display on series metadata',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_META_GROUP_LIST_OPTIONS => [
            'description' => 'Group list options on series list',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_META_KEYWORDS => [
            'description' => 'Show keywords options on series metadata',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_META_CHANNELS => [
            'description' => 'Show channel option on series metadata',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_META_HTML_CONFIGURATION => [
            'description' => 'Show html configuration option on series metadata',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_META_HEADLINE => [
            'description' => 'Show headline option on series metadata',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_META_TEMPLATE => [
            'description' => 'Show template option on series metadata',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_MAGIC_URL => [
            'description' => 'Show magic url option on series',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_SERIES_EDIT_TEMPLATE => [
            'description' => 'Show tab edit template on series',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_META_COPYRIGHT => [
            'description' => 'Edit copyright on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_META_HEADLINE => [
            'description' => 'Edit headline on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_META_KEYWORDS => [
            'description' => 'Edit keywords on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_META_SUBSERIE => [
            'description' => 'Edit subserie on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_META_LICENSE => [
            'description' => 'Edit license on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_TRACKS_OPTIONS => [
            'description' => 'Show tracks options on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_CATEGORY_TAB => [
            'description' => 'Show category tab on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_PEOPLE_TAB => [
            'description' => 'Show people tab on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_OWNER_TAB => [
            'description' => 'Show owner tab on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_SYNC_TAB => [
            'description' => 'Show sync tab on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_MAGIC_URL => [
            'description' => 'Show magic url on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_SHOW_WIZARD_BUTTON => [
            'description' => 'Show wizard button on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
        self::ACCESS_MULTIMEDIA_SHOW_MULTIMEDIA_OBJECT_INFO_URL => [
            'description' => 'Show info urls on multimedia object',
            'dependencies' => [
                PermissionProfile::SCOPE_GLOBAL => [],
                PermissionProfile::SCOPE_PERSONAL => [],
            ],
        ],
    ];

    public static function isRoleTagDefault(string $role): bool
    {
        return 0 === strpos($role, self::PREFIX_ROLE_TAG_DEFAULT);
    }

    public static function getPubChannelForRoleTagDefault(string $role)
    {
        if (self::isRoleTagDefault($role)) {
            return substr($role, strlen(self::PREFIX_ROLE_TAG_DEFAULT));
        }

        return false;
    }

    public static function getRoleTagDefaultForPubChannel(string $cod): string
    {
        return self::PREFIX_ROLE_TAG_DEFAULT.strtoupper($cod);
    }

    public static function isRoleTagDisable(string $role): bool
    {
        return 0 === strpos($role, self::PREFIX_ROLE_TAG_DISABLE);
    }

    public static function getPubChannelForRoleTagDisable(string $role)
    {
        if (self::isRoleTagDisable($role)) {
            return substr($role, strlen(self::PREFIX_ROLE_TAG_DISABLE));
        }

        return false;
    }

    public static function getRoleTagDisableForPubChannel(string $cod): string
    {
        return self::PREFIX_ROLE_TAG_DISABLE.strtoupper($cod);
    }
}
