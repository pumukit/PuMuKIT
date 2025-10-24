<?php

namespace App\Series\Domain;

interface SeriesRepositoryInterface
{
    public function findAll(): iterable;

    public function find(string $id): ?object;

    public function findByFilters(array $filters = []): iterable;
}
