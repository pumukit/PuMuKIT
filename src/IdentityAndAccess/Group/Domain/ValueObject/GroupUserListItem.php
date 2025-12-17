<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Domain\ValueObject;

final class GroupUserListItem
{
    public function __construct(
        public string $id,
        public string $username,
        public string $email,
        public string $fullName
    ) {}
}
