<?php

namespace App\Series\Application\ViewSeriesMultimediaObjects;

final class ViewSeriesMultimediaObjectsRequest
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 10,
        public readonly array $filters = [],
        public readonly ?string $sort = 'title',
        public readonly ?string $order = 'asc',
    ) {}
}
