<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;
use App\Shared\Infrastructure\Security\ValueObject\PermissionType;
use App\Shared\Infrastructure\Security\ValueObject\RegisteredPermission;

final class PermissionRegistry implements PermissionRegistryInterface
{
    private array $permissions = [];

    public function register(array $permissions, string $context, PermissionType $type = PermissionType::DOMAIN): void
    {
        foreach ($permissions as $id => $description) {
            if (!isset($this->permissions[$id])) {
                $this->permissions[$id] = new RegisteredPermission($id, $description, $context, $type);
            }
        }
    }

    public function getAll(): array
    {
        return array_values($this->permissions);
    }

    public function getByContext(string $context): array
    {
        return array_values(array_filter(
            $this->permissions,
            fn(RegisteredPermission $p) => $p->context === $context
        ));
    }

    public function getByType(PermissionType $type): array
    {
        return array_values(array_filter(
            $this->permissions,
            fn(RegisteredPermission $p) => $p->type === $type
        ));
    }

    public function isRegistered(string $permission): bool
    {
        return isset($this->permissions[$permission]);
    }
}
