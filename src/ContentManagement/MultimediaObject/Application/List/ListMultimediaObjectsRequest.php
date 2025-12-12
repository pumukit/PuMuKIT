<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\List;

final class ListMultimediaObjectsRequest
{
    public function __construct(
        public int $page = 1,
        public int $limit = 10,
        public string $sort = 'public_date',
        public string $order = 'desc',
        public array $filters = []
    ) {}
}
