<?php

namespace App\MultimediaObject\Domain;

interface MultimediaObjectRepositoryInterface
{
    public function find(string $id): ?object;

    public function findBySeriesId(string $seriesId): iterable;
}
