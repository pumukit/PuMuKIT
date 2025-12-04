<?php

declare(strict_types=1);

namespace App\Transcoding\Domain\Event;

final class CpuMaintenanceDeactivatedEvent
{
    public const NAME = 'cpu.maintenance.deactivated';

    public function __construct(private string $cpuName) {}

    public function getCpuName(): string
    {
        return $this->cpuName;
    }
}
