<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\Find;

use App\Shared\Domain\Validator\UuidValidator;

final class FindUserValidator
{
    public static function validate(FindUserRequest $request): void
    {
        UuidValidator::validate($request->id, 'User ID');
    }
}
