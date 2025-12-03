<?php

declare(strict_types=1);

namespace App\Playlist\Application\Create;

final class CreatePlaylistRequest
{
    public function __construct(
        public readonly string $ownerId,
        public readonly ?array $title = null
    ) {
        CreatePlaylistValidator::validate($this);
    }
}
