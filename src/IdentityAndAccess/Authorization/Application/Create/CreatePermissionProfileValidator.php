<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Create;

use Pumukit\SchemaBundle\Document\PermissionProfile;

final class CreatePermissionProfileValidator
{
    public static function validate(CreatePermissionProfileRequest $request): void
    {
        if (empty($request->name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        if (strlen($request->name) < 3) {
            throw new \InvalidArgumentException('Name must be at least 3 characters long');
        }

        $validScopes = [
            PermissionProfile::SCOPE_GLOBAL,
            PermissionProfile::SCOPE_PERSONAL,
            PermissionProfile::SCOPE_NONE
        ];

        if (!in_array($request->scope, $validScopes, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid scope: %s. Valid scopes are: %s', $request->scope, implode(', ', $validScopes))
            );
        }

        if (!is_array($request->permissions)) {
            throw new \InvalidArgumentException('Permissions must be an array');
        }
    }
}

