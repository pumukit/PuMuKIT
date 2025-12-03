<?php

declare(strict_types=1);

namespace App\Transcoding\Application\Cpu\ToggleMaintenance;

final class ToggleMaintenanceRequest
{
    public function __construct(
        public readonly bool $activate,
        public readonly string $cpuName,
    ) {}
}







