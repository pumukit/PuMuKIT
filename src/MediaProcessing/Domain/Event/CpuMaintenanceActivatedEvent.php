<?php

declare(strict_types=1);

namespace App\MediaProcessing\Domain\Event;

final class CpuMaintenanceActivatedEvent
{
    public const NAME = 'cpu.maintenance.activated';

    public function __construct(private string $cpuName) {}

    public function getCpuName(): string
    {
        return $this->cpuName;
    }
}
