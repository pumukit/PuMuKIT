<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Find;

final class FindPermissionProfileValidator
{
    public static function validate(FindPermissionProfileRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('PermissionProfile ID cannot be empty');
        }
    }
}

