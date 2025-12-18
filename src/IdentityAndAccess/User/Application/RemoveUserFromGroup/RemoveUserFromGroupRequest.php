<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\RemoveUserFromGroup;

final class RemoveUserFromGroupRequest
{
    public function __construct(
        public string $groupId,
        public string $userId
    ) {}
}
