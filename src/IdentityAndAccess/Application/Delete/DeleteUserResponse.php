<?php

namespace App\IdentityAndAccess\Application\Delete;

final class DeleteUserResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message
    ) {}
}
