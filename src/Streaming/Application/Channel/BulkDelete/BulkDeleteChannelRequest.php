<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\BulkDelete;

final class BulkDeleteChannelRequest
{
    public function __construct(
        public readonly array $channelIds
    ) {
        BulkDeleteChannelValidator::validate($this);
    }
}

