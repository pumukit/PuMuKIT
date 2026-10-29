<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\NewAdminBundle\State\DashboardProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/dashboard/activity', description: 'Multimedia objects and hours added per month in the last twelve months', provider: DashboardProvider::class, security: "is_granted('ROLE_ACCESS_DASHBOARD')"),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class DashboardActivity
{
    public function __construct(
        public array $months = [],
    ) {}
}
