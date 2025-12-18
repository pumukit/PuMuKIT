<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\RemoveUserFromGroup;

final class RemoveUserFromGroupResponse
{
    public function __construct(
        public string $username
    ) {}
}
