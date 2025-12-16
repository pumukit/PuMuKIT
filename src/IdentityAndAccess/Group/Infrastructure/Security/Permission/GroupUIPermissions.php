<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Security\Permission;

final class GroupUIPermissions
{
    public const UI_SHOW_MENU_GROUP = 'ui.group.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_GROUP;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_GROUP => 'Show Group Menu Link in Navigation',
        ];
    }
}
