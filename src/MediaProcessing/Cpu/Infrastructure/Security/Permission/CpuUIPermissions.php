<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Infrastructure\Security\Permission;

final class CpuUIPermissions
{
    public const UI_SHOW_MENU_CPU = 'ui.cpu.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_CPU;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_CPU => 'Show CPU Menu Link in Navigation',
        ];
    }
}
