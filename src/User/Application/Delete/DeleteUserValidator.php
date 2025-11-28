<?php

declare(strict_types=1);

namespace App\User\Application\Delete;

use App\Shared\Domain\Validator\IdValidator;

final class DeleteUserValidator
{
    public static function validate(DeleteUserRequest $request): void
    {
        IdValidator::validate($request->id, 'User ID');
    }
}
