<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Infrastructure\Security\Permission;

final class RoleUIPermissions
{
    public const UI_SHOW_MENU_ROLE = 'ui.role.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_ROLE;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_ROLE => 'Show Role Menu Link in Navigation',
        ];
    }
}
