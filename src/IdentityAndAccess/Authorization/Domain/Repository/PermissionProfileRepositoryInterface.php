<?php

namespace App\IdentityAndAccess\Authorization\Domain\Repository;

use Pumukit\SchemaBundle\Document\PermissionProfile;

interface PermissionProfileRepositoryInterface
{
    public function find(string $id): ?PermissionProfile;

    public function findAll(): array;

    public function findByFilters(array $filters = []): array;

    public function findByFiltersPaginated(array $filters, int $page, int $limit, string $sort, string $order): array;

    public function countByFilters(array $filters = []): int;

    public function findByIds(array $ids): array;

    public function save(PermissionProfile $permissionProfile): void;

    public function delete(PermissionProfile $permissionProfile): void;

    public function findDefault(): ?PermissionProfile;

    public function findByName(string $name): ?PermissionProfile;
}
