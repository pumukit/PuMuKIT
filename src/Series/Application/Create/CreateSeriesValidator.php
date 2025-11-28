<?php

declare(strict_types=1);

namespace App\Series\Application\Create;

use App\Shared\Domain\Validator\IdValidator;

final class CreateSeriesValidator
{
    public static function validate(CreateSeriesRequest $request): void
    {
        IdValidator::validate($request->ownerId, 'Owner ID');
    }
}
