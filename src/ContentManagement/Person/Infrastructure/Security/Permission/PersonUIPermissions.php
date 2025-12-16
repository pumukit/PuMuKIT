<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Infrastructure\Security\Permission;

final class PersonUIPermissions
{
    public const UI_SHOW_MENU_PERSON = 'ui.person.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_PERSON;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_PERSON => 'Show Person Menu Link in Navigation',
        ];
    }
}
