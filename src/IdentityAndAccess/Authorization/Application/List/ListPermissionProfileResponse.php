<?php

namespace App\IdentityAndAccess\Authorization\Application\List;

final class ListPermissionProfileResponse
{
    public function __construct(
        public array $permissionProfiles,
        public int $total
    ) {}
}
