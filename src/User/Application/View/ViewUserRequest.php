<?php

declare(strict_types=1);

namespace App\User\Application\View;

final class ViewUserRequest
{
    public function __construct(
        public readonly string $id
    ) {}
}
