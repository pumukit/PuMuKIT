<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\Create;

final class CreateGroupValidator
{
    public static function validate(CreateGroupRequest $request): void
    {
        if (empty($request->key)) {
            throw new \InvalidArgumentException('Key cannot be empty');
        }

        if (strlen($request->key) < 2) {
            throw new \InvalidArgumentException('Key must be at least 2 characters long');
        }

        if (!preg_match('/^\w*$/', $request->key)) {
            throw new \InvalidArgumentException('Key can only contain alphanumeric characters and underscores');
        }

        if (empty($request->name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        $validOrigins = ['local', 'cas', 'ldap'];
        if (!in_array($request->origin, $validOrigins, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid origin: %s. Valid origins are: %s', $request->origin, implode(', ', $validOrigins))
            );
        }
    }
}
