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

        if ($request->name !== null && empty($request->name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        if ($request->name !== null && strlen($request->name) < 3) {
            throw new \InvalidArgumentException('Name must be at least 3 characters long');
        }

        if ($request->scope !== null) {
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
        }

        if ($request->permissions !== null && !is_array($request->permissions)) {
            throw new \InvalidArgumentException('Permissions must be an array');
        }
    }
}

