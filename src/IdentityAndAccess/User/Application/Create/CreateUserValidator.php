<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\Create;

final class CreateUserValidator
{
    public static function validate(CreateUserRequest $request): void
    {
        if (empty($request->username)) {
            throw new \InvalidArgumentException('Username cannot be empty');
        }

        if (strlen($request->username) < 3) {
            throw new \InvalidArgumentException('Username must be at least 3 characters long');
        }

        if (empty($request->email)) {
            throw new \InvalidArgumentException('Email cannot be empty');
        }

        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email format: %s', $request->email)
            );
        }

        $validOrigins = ['local', 'cas', 'ldap'];
        if (!in_array($request->origin, $validOrigins, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid origin: %s. Valid origins are: %s', $request->origin, implode(', ', $validOrigins))
            );
        }
    }
}
