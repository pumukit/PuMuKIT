<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\List;

final class ListChannelsRequest
{
    public function __construct(
        public int $page = 1,
        public int $limit = 10,
        public string $sort = 'name.en',
        public string $order = 'asc'
    ) {}
}
