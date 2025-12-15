<?php

declare(strict_types=1);

namespace App\Playlist\Application\Delete;

final class DeletePlaylistResponse
{
    public function __construct(
        public bool $success,
        public string $message
    ) {}
}
