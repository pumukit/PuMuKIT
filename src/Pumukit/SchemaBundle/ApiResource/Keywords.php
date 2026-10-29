<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Pumukit\SchemaBundle\State\KeywordsProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/keywords/multimedia_objects', description: 'Most used keywords of the multimedia objects in the request locale', provider: KeywordsProvider::class, security: "is_granted('ROLE_SCOPE_GLOBAL') or is_granted('ROLE_SCOPE_PERSONAL') or is_granted('ROLE_ACCESS_API')", name: 'api_keywords_multimedia_objects'),
        new Get(uriTemplate: '/keywords/series', description: 'Most used keywords of the series in the request locale', provider: KeywordsProvider::class, security: "is_granted('ROLE_SCOPE_GLOBAL') or is_granted('ROLE_SCOPE_PERSONAL') or is_granted('ROLE_ACCESS_API')", name: 'api_keywords_series'),
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
)]
final class Keywords
{
    public function __construct(
        public array $items = [],
    ) {}
}
