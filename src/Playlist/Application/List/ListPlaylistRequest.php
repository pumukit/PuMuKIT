<?php

declare(strict_types=1);

namespace App\Playlist\Application\List;

final class ListPlaylistRequest
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 10,
        public readonly array $filters = [],
        public readonly ?string $sort = null,
        public readonly ?string $order = null
    ) {
        ListPlaylistValidator::validate($this);
    }
}
