<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\List;

final class ListSeriesRequest
{
    public function __construct(
        public readonly ?int $page = 1,
        public readonly ?int $limit = 20,
        public readonly array $filters = [],
        public readonly string $sort = 'rank',
        public readonly string $order = 'asc'
    ) {
        ListSeriesValidator::validate($this);
    }
}
