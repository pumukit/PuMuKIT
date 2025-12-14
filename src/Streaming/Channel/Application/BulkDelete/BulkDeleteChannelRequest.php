<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\BulkDelete;

final class BulkDeleteChannelRequest
{
    public function __construct(
        public array $channelIds
    ) {}
    }
}
