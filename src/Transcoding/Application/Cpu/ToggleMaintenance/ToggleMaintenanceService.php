<?php

declare(strict_types=1);

namespace App\Transcoding\Application\Cpu\ToggleMaintenance;

use App\Transcoding\Domain\Event\CpuMaintenanceActivatedEvent;
use App\Transcoding\Domain\Event\CpuMaintenanceDeactivatedEvent;
use App\Transcoding\Domain\Exception\CpuNotFoundException;
use App\Transcoding\Domain\Repository\CpuRepositoryInterface;
use Pumukit\EncoderBundle\Document\CpuStatus;
use App\Shared\Domain\EventBusInterface;

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
            $this->eventBus->dispatch(
                new CpuMaintenanceActivatedEvent($request->cpuName),
                CpuMaintenanceActivatedEvent::NAME
            );
        } else {
            $this->deactivateMaintenance($request->cpuName);
            $this->eventBus->dispatch(
                new CpuMaintenanceDeactivatedEvent($request->cpuName),
                CpuMaintenanceDeactivatedEvent::NAME
            );
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
        } elseif ($cpuStatus->getStatus() !== CpuStatus::STATUS_MAINTENANCE) {
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

