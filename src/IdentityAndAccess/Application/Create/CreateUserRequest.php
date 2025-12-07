<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Application\Create;

final class CreateUserRequest
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly ?string $fullName = null,
        public readonly ?string $password = null,
        public readonly bool $enabled = false,
        public readonly string $origin = 'local'
    ) {}
}
