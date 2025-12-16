<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Update;

final class UpdatePermissionProfileRequest
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?array $permissions = null,
        public ?bool $system = null,
        public ?bool $default = null,
        public ?string $scope = null
    ) {}
}
