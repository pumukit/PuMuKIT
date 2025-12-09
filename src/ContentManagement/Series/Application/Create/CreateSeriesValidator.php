<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Create;

use App\Shared\Domain\Validator\UuidValidator;

final class CreateSeriesValidator
{
    public static function validate(CreateSeriesRequest $request): void
    {
        UuidValidator::validate($request->ownerId, 'Owner ID');
    }
}
