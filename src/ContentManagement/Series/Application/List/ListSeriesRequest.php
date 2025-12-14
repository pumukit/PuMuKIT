<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\List;

final class ListSeriesRequest
{
    public function __construct(
        public ?int $page = 1,
        public ?int $limit = 20,
        public array $filters = [],
        public string $sort = 'rank',
        public string $order = 'asc'
    ) {}
    }
}
