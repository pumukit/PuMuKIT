<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\AddUserToGroup;

final class AddUserToGroupRequest
{
    public function __construct(
        public string $groupId,
        public string $userId
    ) {}
}
