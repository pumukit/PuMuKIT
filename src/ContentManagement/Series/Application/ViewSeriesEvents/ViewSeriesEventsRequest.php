<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\ViewSeriesEvents;

final class ViewSeriesEventsRequest
{
    public function __construct(
        public int $page = 1,
        public int $limit = 10,
        public array $filters = [],
        public ?string $sort = 'title',
        public ?string $order = 'asc',
    ) {}
}
