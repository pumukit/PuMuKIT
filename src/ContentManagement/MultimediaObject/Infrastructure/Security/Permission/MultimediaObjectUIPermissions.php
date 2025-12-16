<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Infrastructure\Security\Permission;

final class MultimediaObjectUIPermissions
{
    public const UI_SHOW_MENU_MULTIMEDIA_OBJECTS = 'ui.multimedia_object.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_MULTIMEDIA_OBJECTS;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_MULTIMEDIA_OBJECTS => 'Show Multimedia Objects Menu Link in Navigation',
        ];
    }
}
