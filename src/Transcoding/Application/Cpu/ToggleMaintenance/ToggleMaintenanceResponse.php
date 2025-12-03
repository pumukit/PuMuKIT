<?php

declare(strict_types=1);

namespace App\Transcoding\Application\Cpu\ToggleMaintenance;

final class ToggleMaintenanceResponse
{
    public function __construct(
        public readonly string $cpuName,
        public readonly bool $inMaintenance
    ) {}
}

