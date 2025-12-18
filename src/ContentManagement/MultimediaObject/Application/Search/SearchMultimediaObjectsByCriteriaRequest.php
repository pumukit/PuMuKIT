<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\Search;

final class SearchMultimediaObjectsByCriteriaRequest
{
    public function __construct(
        public array $filters,
        public ?string $orderBy = null,
        public ?string $order = null,
        public ?int $limit = null,
        public ?int $offset = null
    ) {}
}
