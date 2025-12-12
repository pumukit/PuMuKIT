<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\Create;

final class CreateUserRequest
{
    public function __construct(
        public string $username,
        public string $email,
        public ?string $fullName = null,
        public ?string $password = null,
        public bool $enabled = false,
        public string $origin = 'local'
    ) {}
}
