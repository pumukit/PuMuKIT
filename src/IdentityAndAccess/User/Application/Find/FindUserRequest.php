<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\Find;

final class FindUserRequest
{
    public function __construct(
        public readonly string $id
    ) {}
}
