<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Delete;

final class DeletePlaylistRequest
{
    public function __construct(
        public string $id
    ) {}
    }
}
