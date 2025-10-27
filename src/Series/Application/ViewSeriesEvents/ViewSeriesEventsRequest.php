<?php

namespace App\Series\Application\ViewSeriesEvents;

final class ViewSeriesEventsRequest
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 10,
        public readonly array $filters = [],
        public readonly ?string $sort = 'title',
        public readonly ?string $order = 'asc',
    ) {}
}
