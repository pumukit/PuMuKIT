<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\StatsBundle\State\AudienceStatsProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/stats/top-videos', description: 'Most viewed multimedia objects of the period', provider: AudienceStatsProvider::class, security: "is_granted('ROLE_ACCESS_STATS')", name: 'stats_top_videos'),
        new Get(uriTemplate: '/stats/top-series', description: 'Most viewed series of the period', provider: AudienceStatsProvider::class, security: "is_granted('ROLE_ACCESS_STATS')", name: 'stats_top_series'),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class StatsTop
{
    public function __construct(
        public int $total = 0,
        public array $items = [],
    ) {}
}
