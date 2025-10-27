<?php

namespace App\Series\Domain;

use Pumukit\SchemaBundle\Document\Series;

interface SeriesRepositoryInterface
{
    public function findAll(): iterable;

    public function find(string $id): ?object;

    public function findByFilters(array $filters = []): iterable;

    public function findMultimediaObjectsBySeries(string $seriesId, int $offset = 0, int $limit = 10, string $sort = 'title', string $order = 'asc'): array;

    public function countMultimediaObjects(string $seriesId): int;

    public function save(Series $series): void;

    public function delete(Series $series): void;
}
