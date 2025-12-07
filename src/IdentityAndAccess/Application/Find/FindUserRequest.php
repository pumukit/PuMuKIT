<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Application\Find;

final class FindUserRequest
{
    public function __construct(
        public readonly string $id
    ) {}
}
