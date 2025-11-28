<?php

declare(strict_types=1);

namespace App\User\Application\Find;

use App\Shared\Domain\Validator\IdValidator;

final class FindUserValidator
{
    public static function validate(FindUserRequest $request): void
    {
        IdValidator::validate($request->id, 'User ID');
    }
}
