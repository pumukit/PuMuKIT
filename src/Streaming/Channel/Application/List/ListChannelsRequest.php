<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\List;

final class ListChannelsRequest
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 10,
        public readonly string $sort = 'name.en',
        public readonly string $order = 'asc'
    ) {}
}
