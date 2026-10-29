<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\NewAdminBundle\State\DashboardProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/dashboard/summary', description: 'Series, multimedia objects and hours of content in the user scope', provider: DashboardProvider::class, security: "is_granted('ROLE_ACCESS_DASHBOARD')"),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class DashboardSummary
{
    public function __construct(
        public int $series = 0,
        public int $multimediaObjects = 0,
        public int $duration = 0,
    ) {}
}
