<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Application\ToggleMaintenance;

final class ToggleMaintenanceResponse
{
    public function __construct(
        public string $cpuName,
        public bool $inMaintenance
    ) {}
}
