<?php

declare(strict_types=1);

namespace App\User\Application\View;

use App\Shared\Domain\Validator\IdValidator;

final class ViewUserValidator
{
    public static function validate(ViewUserRequest $request): void
    {
        IdValidator::validate($request->id, 'User ID');
    }
}
