<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\NewAdminBundle\State\DashboardProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/dashboard/jobs', description: 'Encoder jobs waiting, executing and paused, and finished or failed in the last 24 hours', provider: DashboardProvider::class, security: "is_granted('ROLE_ACCESS_DASHBOARD') and is_granted('ROLE_ACCESS_JOBS') and (is_granted('ROLE_SCOPE_GLOBAL') or is_granted('ROLE_SUPER_ADMIN'))"),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class DashboardJobs
{
    public function __construct(
        public array $items = [],
    ) {}
}
