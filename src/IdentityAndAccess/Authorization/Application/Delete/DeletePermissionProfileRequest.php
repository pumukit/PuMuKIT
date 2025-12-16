<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Delete;

final class DeletePermissionProfileRequest
{
    public function __construct(
        public string $id
    ) {}
}
