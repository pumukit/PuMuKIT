<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\Delete;

use App\Shared\Domain\Validator\UuidValidator;

final class DeleteChannelValidator
{
    public static function validate(DeleteChannelRequest $request): void
    {
        UuidValidator::validate($request->id, 'Channel ID');
    }
}

