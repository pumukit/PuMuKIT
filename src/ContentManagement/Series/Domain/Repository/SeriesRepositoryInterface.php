<?php

namespace App\ContentManagement\Series\Domain\Repository;

use Pumukit\SchemaBundle\Document\Series;

interface SeriesRepositoryInterface
{
    public function findAll(): iterable;

    public function find(string $id): ?object;

    public function findByFilters(array $filters = []): iterable;

    public function findMultimediaObjectsBySeries(string $seriesId, int $offset = 0, int $limit = 10, string $sort = 'title', string $order = 'asc'): array;

    public function countEventMultimediaObjects(string $seriesId): int;

    public function countMultimediaObjects(string $seriesId): int;

    public function findByFiltersPaginated(array $filters, int $page, int $limit, ?string $sort = null, ?string $order = null): array;

    public function countByFilters(array $filters): int;

    public function findEventsBySeries(string $seriesId, int $offset = 0, int $limit = 10, string $sort = 'title', string $order = 'asc'): array;

    public function save(Series $series): void;

    public function delete(Series $series): void;

    public function countEvents(string $seriesId): int;
}
