<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Domain\Repository;

use Pumukit\SchemaBundle\Document\Role;

interface RoleRepositoryInterface
{
    public function find(string $id): ?Role;

    public function findByCod(string $cod): ?Role;

    public function findAll(int $page = 1, int $limit = 10, ?array $sort = null, ?array $filters = []): iterable;

    public function countAll(): int;

    public function save(Role $role): void;

    public function delete(Role $role): void;
}
