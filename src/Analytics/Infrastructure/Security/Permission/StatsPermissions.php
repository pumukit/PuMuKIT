<?php

declare(strict_types=1);

namespace App\Analytics\Infrastructure\Security\Permission;

final class StatsPermissions
{
    public const VIEW_STATS = 'view_stats';

    public static function all(): array
    {
        return [
            self::VIEW_STATS => 'View statistics',
        ];
    }
}
