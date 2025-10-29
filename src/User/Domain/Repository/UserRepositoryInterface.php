<?php

namespace App\User\Domain\Repository;

interface UserRepositoryInterface
{
    public function find(string $id): ?object;

    public function findAllUsers(): array;

    public function findByFilters(array $filters = []): array;

    public function findByIds(array $ids): array;
}
