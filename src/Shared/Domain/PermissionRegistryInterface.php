<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface PermissionRegistryInterface
{
    public function register(array $permissions, string $context): void;

    public function getAll(): array;

    public function getByContext(string $context): array;

    public function isRegistered(string $permission): bool;
}
