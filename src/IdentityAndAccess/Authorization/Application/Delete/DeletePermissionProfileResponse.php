<?php

namespace App\IdentityAndAccess\Authorization\Application\Delete;

final class DeletePermissionProfileResponse
{
    public function __construct(
        public bool $success,
        public string $message
    ) {}
}
