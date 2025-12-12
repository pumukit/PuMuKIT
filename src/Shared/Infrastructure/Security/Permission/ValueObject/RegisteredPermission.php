<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\Permission\ValueObject;

final class RegisteredPermission
{
    public function __construct(
        public string $id,
        public string $description,
        public string $context,
        public PermissionType $type = PermissionType::DOMAIN
    ) {}
}
