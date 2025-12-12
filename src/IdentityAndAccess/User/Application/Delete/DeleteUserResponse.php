<?php

namespace App\IdentityAndAccess\User\Application\Delete;

final class DeleteUserResponse
{
    public function __construct(
        public bool $success,
        public string $message
    ) {}
}
