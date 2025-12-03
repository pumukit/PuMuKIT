<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\BulkDelete;

final class BulkDeleteChannelsRequest
{
    public function __construct(
        public readonly array $channelIds
    ) {
        BulkDeleteChannelsValidator::validate($this);
    }
}

