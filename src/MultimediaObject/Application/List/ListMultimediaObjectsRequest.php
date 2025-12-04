<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\List;

final class ListMultimediaObjectsRequest
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 10,
        public readonly string $sort = 'public_date',
        public readonly string $order = 'desc',
        public readonly array $filters = []
    ) {}
}
