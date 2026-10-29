<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\StatsBundle\State\AudienceStatsProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/stats/heatmap', description: 'Views by day of the week (Monday first) and hour', provider: AudienceStatsProvider::class, security: "is_granted('ROLE_ACCESS_STATS')"),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class StatsHeatmap
{
    public function __construct(
        public array $cells = [],
        public int $max = 0,
    ) {}
}
