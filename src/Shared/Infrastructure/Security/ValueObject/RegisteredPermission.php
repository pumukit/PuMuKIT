<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\ValueObject;

final class RegisteredPermission
{
    public function __construct(
        public readonly string $id,
        public readonly string $description,
        public readonly string $context,
        public readonly PermissionType $type = PermissionType::DOMAIN
    ) {}
}
