<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Domain\Repository;

use Pumukit\SchemaBundle\Document\Group;

interface GroupRepositoryInterface
{
    public function delete(Group $group): void;

    public function save(Group $group): void;

    public function findByIds(array $ids): array;

    public function countByFilters(array $filters = []): int;

    public function findByFiltersPaginated(array $filters, int $page, int $limit, string $sort, string $order): array;

    public function find(string $id): ?Group;

    public function findByKey(string $key): ?Group;

    public function findAllGroups(): array;
}
