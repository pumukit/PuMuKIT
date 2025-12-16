<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\View;

final class ViewPermissionProfileValidator
{
    public static function validate(ViewPermissionProfileRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('PermissionProfile ID cannot be empty');
        }

        if (empty($request->tab)) {
            throw new \InvalidArgumentException('Tab cannot be empty');
        }
    }
}
