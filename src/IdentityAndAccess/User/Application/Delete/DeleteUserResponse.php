<?php

namespace App\IdentityAndAccess\User\Application\Delete;

final class DeleteUserResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message
    ) {}
}
