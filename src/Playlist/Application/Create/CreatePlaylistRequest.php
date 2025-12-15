<?php

declare(strict_types=1);

namespace App\Playlist\Application\Create;

final class CreatePlaylistRequest
{
    public function __construct(
        public string $ownerId,
        public ?array $title = null
    ) {}
}
