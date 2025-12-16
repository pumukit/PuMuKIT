<?php

declare(strict_types=1);

namespace App\Analytics\Infrastructure\Security\Permission;

final class StatsUIPermissions
{
    public const UI_SHOW_MENU_STATS = 'ui.stats.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_STATS;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_STATS => 'Show Stats Menu Link in Navigation',
        ];
    }
}
