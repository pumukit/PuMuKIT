<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Update;

use Pumukit\SchemaBundle\Document\PermissionProfile;

final class UpdatePermissionProfileValidator
{
    public static function validate(UpdatePermissionProfileRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('PermissionProfile ID cannot be empty');
        }

        if (null !== $request->name && empty($request->name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        if (null !== $request->name && strlen($request->name) < 3) {
            throw new \InvalidArgumentException('Name must be at least 3 characters long');
        }

        if (null !== $request->scope) {
            $validScopes = [
                PermissionProfile::SCOPE_GLOBAL,
                PermissionProfile::SCOPE_PERSONAL,
                PermissionProfile::SCOPE_NONE,
            ];

            if (!in_array($request->scope, $validScopes, true)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid scope: %s. Valid scopes are: %s', $request->scope, implode(', ', $validScopes))
                );
            }
        }

        if (null !== $request->permissions && !is_array($request->permissions)) {
            throw new \InvalidArgumentException('Permissions must be an array');
        }
    }
}
