<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Application\View;

final class ViewUserRequest
{
    public function __construct(
        public readonly string $id
    ) {}
}
