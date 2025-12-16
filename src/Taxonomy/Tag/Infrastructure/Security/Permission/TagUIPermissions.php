<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Infrastructure\Security\Permission;

final class TagUIPermissions
{
    public const UI_SHOW_MENU_TAG = 'ui.tag.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_TAG;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_TAG => 'Show Tag Menu Link in Navigation',
        ];
    }
}
