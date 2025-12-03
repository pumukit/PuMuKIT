<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\BulkDelete;

use InvalidArgumentException;

final class BulkDeleteChannelValidator
{
    public static function validate(BulkDeleteChannelRequest $request): void
    {
        if (empty($request->channelIds)) {
            throw new InvalidArgumentException('At least one channel ID must be provided');
        }

        if (!is_array($request->channelIds)) {
            throw new InvalidArgumentException('Channel IDs must be an array');
        }

        foreach ($request->channelIds as $channelId) {
            if (!is_string($channelId) || empty($channelId)) {
                throw new InvalidArgumentException('Each channel ID must be a non-empty string');
            }

            if (!preg_match('/^[a-f0-9]{24}$/i', $channelId)) {
                throw new InvalidArgumentException(sprintf('Invalid MongoDB ObjectId format: %s', $channelId));
            }
        }
    }
}

