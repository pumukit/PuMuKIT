<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Infrastructure\Security\Permission;

final class SeriesUIPermissions
{
    public const UI_SHOW_MENU_SERIES = 'ui.series.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_SERIES;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_SERIES => 'Show Series Menu Link in Navigation',
        ];
    }
}
