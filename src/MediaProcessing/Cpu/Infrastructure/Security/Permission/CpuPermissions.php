<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Infrastructure\Security\Permission;

final class CpuPermissions
{
    public const VIEW = 'cpu.view';
    public const MAINTENANCE = 'cpu.maintenance';

    public static function all(): array
    {
        return [
            self::VIEW => 'View CPU status',
            self::MAINTENANCE => 'Enable CPU maintenance mode',
        ];
    }
}
