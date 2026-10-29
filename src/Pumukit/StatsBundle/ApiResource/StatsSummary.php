<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\StatsBundle\State\AudienceStatsProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/stats/summary', description: 'Audience totals of the period and of the previous one', provider: AudienceStatsProvider::class, security: "is_granted('ROLE_ACCESS_STATS')"),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class StatsSummary
{
    public function __construct(
        public int $views = 0,
        public int $previousViews = 0,
        public int $uniqueViews = 0,
        public int $previousUniqueViews = 0,
        public int $viewedObjects = 0,
        public int $viewedSeries = 0,
        public ?int $publishedObjects = null,
        public ?int $unviewedObjects = null,
    ) {}
}
