<?php

namespace App\Series\Application\ListSeries;

class ListSeriesRequest
{
    public function __construct(
        public readonly ?int $page = 1,
        public readonly ?int $limit = 20,
        public readonly array $filters = [],
        public readonly string $sort = 'rank',
        public readonly string $order = 'asc'
    ) {}
}
