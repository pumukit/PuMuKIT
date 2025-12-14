<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Application\ToggleMaintenance;

final class ToggleMaintenanceValidator
{
    public static function validate(ToggleMaintenanceRequest $request): void
    {
        if (empty($request->cpuName)) {
            throw new \InvalidArgumentException('CPU name cannot be empty');
        }
    }
}

