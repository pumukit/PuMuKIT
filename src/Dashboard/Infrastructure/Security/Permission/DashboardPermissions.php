<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Security\Permission;

final class DashboardPermissions
{
    public const VIEW_DASHBOARD = 'view_dashboard';

    public static function all(): array
    {
        return [
            self::VIEW_DASHBOARD => 'View dashboard',
        ];
    }
}
