<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\BulkDelete;

final class BulkDeletePlaylistRequest
{
    public function __construct(
        public array $playlistIds
    ) {
        BulkDeletePlaylistValidator::validate($this);
    }
}
