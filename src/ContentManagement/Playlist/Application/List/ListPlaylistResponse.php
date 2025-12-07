<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\List;

final class ListPlaylistResponse
{
    public function __construct(
        public readonly array $playlists,
        public readonly int $total
    ) {}
}
