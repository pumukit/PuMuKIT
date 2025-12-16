<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Delete;

final class DeletePermissionProfileValidator
{
    public static function validate(DeletePermissionProfileRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('PermissionProfile ID cannot be empty');
        }
    }
}
