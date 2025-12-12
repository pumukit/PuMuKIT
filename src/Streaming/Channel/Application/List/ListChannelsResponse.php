<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\List;

final class ListChannelsResponse
{
    public function __construct(
        public iterable $channels,
        public int $total,
        public int $page,
        public int $limit
    ) {}
}
