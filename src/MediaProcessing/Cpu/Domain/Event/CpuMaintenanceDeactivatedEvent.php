<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Domain\Event;

use App\Shared\Domain\DomainEvent;

final class CpuMaintenanceDeactivatedEvent extends DomainEvent
{
    public function __construct(
        public string $cpuName
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'cpu.maintenance.deactivated';
    }
}
