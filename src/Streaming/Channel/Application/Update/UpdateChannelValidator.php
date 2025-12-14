<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\Update;

use App\Shared\Domain\Validator\UuidValidator;

final class UpdateChannelValidator
{
    public static function validate(UpdateChannelRequest $request): void
    {
        UuidValidator::validate($request->id, 'Channel ID');

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

