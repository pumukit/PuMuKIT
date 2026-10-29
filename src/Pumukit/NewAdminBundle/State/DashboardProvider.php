<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Pumukit\NewAdminBundle\ApiResource\DashboardActivity;
use Pumukit\NewAdminBundle\ApiResource\DashboardJobs;
use Pumukit\NewAdminBundle\ApiResource\DashboardStatus;
use Pumukit\NewAdminBundle\ApiResource\DashboardStorage;
use Pumukit\NewAdminBundle\ApiResource\DashboardSummary;
use Pumukit\NewAdminBundle\Services\DashboardStatsService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DashboardProvider implements ProviderInterface
{
    public function __construct(private readonly DashboardStatsService $dashboard) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        return match ($operation->getClass()) {
            DashboardSummary::class => new DashboardSummary(...$this->dashboard->summary()),
            DashboardActivity::class => new DashboardActivity(...$this->dashboard->activity()),
            DashboardStatus::class => new DashboardStatus(...$this->dashboard->status()),
            DashboardJobs::class => new DashboardJobs(...$this->dashboard->jobs()),
            DashboardStorage::class => new DashboardStorage(...$this->dashboard->storage()),
            default => throw new NotFoundHttpException(),
        };
    }
}
