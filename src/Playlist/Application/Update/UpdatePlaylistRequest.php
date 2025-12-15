<?php

declare(strict_types=1);

namespace App\Playlist\Application\Update;

final class UpdatePlaylistRequest
{
    public function __construct(
        public string $id,
        public ?array $title = null,
        public ?array $subtitle = null,
        public ?array $description = null,
        public ?array $header = null,
        public ?array $footer = null,
        public ?string $comments = null,
        public ?array $keywords = null,
        public ?bool $announce = null,
        public ?bool $hide = null,
        public ?\DateTimeInterface $publicDate = null,
        public ?array $properties = null
    ) {}
    }
}
