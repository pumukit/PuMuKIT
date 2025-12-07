<?php

declare(strict_types=1);

namespace App\MediaProcessing\Application\Cpu\ToggleMaintenance;

final class ToggleMaintenanceRequest
{
    public function __construct(
        public readonly bool $activate,
        public readonly string $cpuName,
    ) {}
}
