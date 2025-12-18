<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\AddUserToGroup;

final class AddUserToGroupResponse
{
    public function __construct(
        public string $username
    ) {}
}
