<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\View;

use App\Shared\Domain\Validator\UuidValidator;

final class ViewChannelValidator
{
    public static function validate(ViewChannelRequest $request): void
    {
        UuidValidator::validate($request->id, 'Channel ID');
    }
}

