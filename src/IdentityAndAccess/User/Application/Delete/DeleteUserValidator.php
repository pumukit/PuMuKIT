<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\Delete;

use App\Shared\Domain\Validator\UuidValidator;

final class DeleteUserValidator
{
    public static function validate(DeleteUserRequest $request): void
    {
        UuidValidator::validate($request->id, 'User ID');
    }
}
