<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\Update;

final class UpdateChannelRequest
{
    public function __construct(
        public string $id,
        public array $name,
        public array $description,
        public string $url,
        public string $sourceName,
        public ?string $passwd = null,
        public ?string $liveType = null,
        public ?string $ipSource = null,
        public bool $indexPlay = false,
        public bool $broadcasting = false,
        public bool $debug = false,
        public bool $chat = false
    ) {}
}
