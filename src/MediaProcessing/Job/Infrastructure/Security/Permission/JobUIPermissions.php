<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Infrastructure\Security\Permission;

final class JobUIPermissions
{
    public const UI_SHOW_MENU_JOB = 'ui.job.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_JOB;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_JOB => 'Show Job Menu Link in Navigation',
        ];
    }
}
