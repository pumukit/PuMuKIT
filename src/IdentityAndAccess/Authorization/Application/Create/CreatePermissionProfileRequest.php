<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Create;

final class CreatePermissionProfileRequest
{
    public function __construct(
        public string $name,
        public array $permissions = [],
        public bool $system = false,
        public bool $default = false,
        public string $scope = 'ROLE_SCOPE_PERSONAL'
    ) {}
}
