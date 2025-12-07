<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Delete;

final class DeletePlaylistResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message
    ) {}
}
