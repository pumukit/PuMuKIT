<?php

declare(strict_types=1);

namespace App\Playlist\Application\List;

final class ListPlaylistResponse
{
    public function __construct(
        public array $playlists,
        public int $total
    ) {}
}
