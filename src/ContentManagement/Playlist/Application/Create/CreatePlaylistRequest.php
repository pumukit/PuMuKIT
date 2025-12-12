<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Create;

final class CreatePlaylistRequest
{
    public function __construct(
        public string $ownerId,
        public ?array $title = null
    ) {
        CreatePlaylistValidator::validate($this);
    }
}
