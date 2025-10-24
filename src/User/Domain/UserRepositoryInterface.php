<?php

namespace App\User\Domain;

interface UserRepositoryInterface
{
    public function findAllUsers(): array;
    public function findByFilters(array $filters = []): array;
    public function findByIds(array $ids): array;
}
