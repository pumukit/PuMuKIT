<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Infrastructure\Security\Permission\ValueObject\PermissionType;

interface PermissionRegistryInterface
{
    public function register(array $permissions, string $context, PermissionType $permissionType): void;

    public function getAll(): array;

    public function getByContext(string $context): array;

    public function isRegistered(string $permission): bool;
}
