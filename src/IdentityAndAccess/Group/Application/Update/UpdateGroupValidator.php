<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\Update;

final class UpdateGroupValidator
{
    public static function validate(UpdateGroupRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('Group ID cannot be empty');
        }

        if (null !== $request->key && empty($request->key)) {
            throw new \InvalidArgumentException('Key cannot be empty');
        }

        if (null !== $request->key && strlen($request->key) < 2) {
            throw new \InvalidArgumentException('Key must be at least 2 characters long');
        }

        if (null !== $request->key && !preg_match('/^\w*$/', $request->key)) {
            throw new \InvalidArgumentException('Key can only contain alphanumeric characters and underscores');
        }

        if (null !== $request->name && empty($request->name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        if (null !== $request->origin) {
            $validOrigins = ['local', 'cas', 'ldap'];
            if (!in_array($request->origin, $validOrigins, true)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid origin: %s. Valid origins are: %s', $request->origin, implode(', ', $validOrigins))
                );
            }
        }
    }
}
