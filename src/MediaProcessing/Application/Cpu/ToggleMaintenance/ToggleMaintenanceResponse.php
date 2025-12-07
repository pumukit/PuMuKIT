<?php

declare(strict_types=1);

namespace App\MediaProcessing\Application\Cpu\ToggleMaintenance;

final class ToggleMaintenanceResponse
{
    public function __construct(
        public readonly string $cpuName,
        public readonly bool $inMaintenance
    ) {}
}
