<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\Create;

final class CreateChannelRequest
{
    public function __construct(
        public readonly array $name,
        public readonly array $description,
        public readonly string $url,
        public readonly string $sourceName,
        public readonly ?string $passwd = null,
        public readonly ?string $liveType = null,
        public readonly ?string $ipSource = null,
        public readonly bool $indexPlay = false,
        public readonly bool $broadcasting = false,
        public readonly bool $debug = false,
        public readonly bool $chat = false
    ) {}
}


