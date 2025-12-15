<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\Create;

final class CreateChannelValidator
{
    public static function validate(CreateChannelRequest $request): void
    {
        if (empty($request->name)) {
            throw new \InvalidArgumentException('Channel name cannot be empty');
        }

        if (empty($request->url)) {
            throw new \InvalidArgumentException('Channel URL cannot be empty');
        }

        if (empty($request->sourceName)) {
            throw new \InvalidArgumentException('Channel source name cannot be empty');
        }
    }
}
