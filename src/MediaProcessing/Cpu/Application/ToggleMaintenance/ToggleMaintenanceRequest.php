<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Application\ToggleMaintenance;

final class ToggleMaintenanceRequest
{
    public function __construct(
        public readonly bool $activate,
        public readonly string $cpuName,
    ) {}
}
