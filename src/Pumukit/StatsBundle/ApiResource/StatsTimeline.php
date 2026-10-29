<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\StatsBundle\State\AudienceStatsProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/stats/timeline', description: 'Views per day, week or month, aligned with the previous period', provider: AudienceStatsProvider::class, security: "is_granted('ROLE_ACCESS_STATS')"),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class StatsTimeline
{
    public function __construct(
        public string $interval = 'day',
        public array $points = [],
    ) {}
}
