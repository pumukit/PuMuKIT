<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Find;

final class FindPermissionProfileRequest
{
    public function __construct(
        public string $id
    ) {}
}
