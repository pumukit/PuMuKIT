<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\List;

final class ListChannelsResponse
{
    public function __construct(
        public readonly iterable $channels,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit
    ) {}
}
