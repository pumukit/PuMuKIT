<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\List;

final class ListPlaylistRequest
{
    public function __construct(
        public int $page = 1,
        public int $limit = 10,
        public array $filters = [],
        public ?string $sort = null,
        public ?string $order = null
    ) {
        ListPlaylistValidator::validate($this);
    }
}
