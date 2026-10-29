<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\StatsBundle\State\AudienceStatsProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/stats/devices', description: 'Views by device type', provider: AudienceStatsProvider::class, security: "is_granted('ROLE_ACCESS_STATS')"),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class StatsDevices
{
    public function __construct(
        public int $total = 0,
        public array $items = [],
    ) {}
}
