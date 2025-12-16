<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Security\Permission;

final class UserUIPermissions
{
    public const UI_SHOW_MENU_USER = 'ui.user.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_USER;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_USER => 'Show User Menu Link in Navigation',
        ];
    }
}
