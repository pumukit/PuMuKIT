<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\NewAdminBundle\State\DashboardProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/dashboard/status', description: 'Multimedia objects by publication status and without playable media', provider: DashboardProvider::class, security: "is_granted('ROLE_ACCESS_DASHBOARD')"),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class DashboardStatus
{
    public function __construct(
        public array $items = [],
    ) {}
}
