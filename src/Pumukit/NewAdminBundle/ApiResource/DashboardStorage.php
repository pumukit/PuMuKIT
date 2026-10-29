<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\NewAdminBundle\State\DashboardProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/dashboard/storage', description: 'Used and total space of the storage destinations', provider: DashboardProvider::class, security: "is_granted('ROLE_ACCESS_DASHBOARD') and (is_granted('ROLE_SCOPE_GLOBAL') or is_granted('ROLE_SUPER_ADMIN'))"),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class DashboardStorage
{
    public function __construct(
        public array $items = [],
    ) {}
}
