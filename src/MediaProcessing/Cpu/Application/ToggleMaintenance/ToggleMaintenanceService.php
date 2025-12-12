<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Application\ToggleMaintenance;

use App\MediaProcessing\Cpu\Domain\Event\CpuMaintenanceActivatedEvent;
use App\MediaProcessing\Cpu\Domain\Event\CpuMaintenanceDeactivatedEvent;
use App\MediaProcessing\Cpu\Domain\Exception\CpuNotFoundException;
use App\MediaProcessing\Cpu\Domain\Repository\CpuRepositoryInterface;
use App\Shared\Domain\EventBusInterface;
use Pumukit\EncoderBundle\Document\CpuStatus;

final class ToggleMaintenanceService
{
    public function __construct(
        private readonly array $cpus,
        private readonly CpuRepositoryInterface $cpuRepository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(ToggleMaintenanceRequest $request): ToggleMaintenanceResponse
    {
        if (!isset($this->cpus[$request->cpuName])) {
            throw new CpuNotFoundException($request->cpuName);
        }

        if ($request->activate) {
            $this->activateMaintenance($request->cpuName);
            $this->eventBus->dispatch(new CpuMaintenanceActivatedEvent($request->cpuName));
        } else {
            $this->deactivateMaintenance($request->cpuName);
            $this->eventBus->dispatch(new CpuMaintenanceDeactivatedEvent($request->cpuName));
        }

        return new ToggleMaintenanceResponse(
            cpuName: $request->cpuName,
            inMaintenance: $request->activate
        );
    }

    private function activateMaintenance(string $cpuName): void
    {
        $cpuStatus = $this->cpuRepository->findByName($cpuName);

        if (!$cpuStatus) {
            $cpuStatus = new CpuStatus();
            $cpuStatus->setName($cpuName);
            $cpuStatus->setStatus(CpuStatus::STATUS_MAINTENANCE);
        } elseif (CpuStatus::STATUS_MAINTENANCE !== $cpuStatus->getStatus()) {
            $cpuStatus->setStatus(CpuStatus::STATUS_MAINTENANCE);
        }

        $this->cpuRepository->save($cpuStatus);
    }

    private function deactivateMaintenance(string $cpuName): void
    {
        $cpuStatus = $this->cpuRepository->findByName($cpuName);

        if ($cpuStatus) {
            $this->cpuRepository->delete($cpuStatus);
        }
    }
}
