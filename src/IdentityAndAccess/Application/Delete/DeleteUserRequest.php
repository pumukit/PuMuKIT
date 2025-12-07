<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Application\Delete;

final class DeleteUserRequest
{
    public function __construct(
        public readonly string $id
    ) {}
}
