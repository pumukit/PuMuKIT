<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\View;

use App\Shared\Domain\Validator\UuidValidator;

final class ViewUserValidator
{
    public static function validate(ViewUserRequest $request): void
    {
        UuidValidator::validate($request->id, 'User ID');
    }
}
