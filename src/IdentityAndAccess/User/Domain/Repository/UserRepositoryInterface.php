<?php

namespace App\IdentityAndAccess\User\Domain\Repository;

use Pumukit\SchemaBundle\Document\User;

interface UserRepositoryInterface
{
    public function find(string $id): ?User;

    public function findAllUsers(): array;

    public function findByFilters(array $filters = []): array;

    public function findByFiltersPaginated(array $filters, int $page, int $limit, string $sort, string $order): array;

    public function countByFilters(array $filters = []): int;

    public function findByIds(array $ids): array;

    public function save(User $user): void;

    public function delete(User $user): void;
}
