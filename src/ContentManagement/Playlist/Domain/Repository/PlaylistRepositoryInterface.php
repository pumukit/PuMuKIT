<?php

namespace App\ContentManagement\Playlist\Domain\Repository;

use Pumukit\SchemaBundle\Document\Series;

interface PlaylistRepositoryInterface
{
    public function findAll(): iterable;

    public function find(string $id): ?object;

    public function findByFilters(array $filters = []): iterable;

    public function findByFiltersPaginated(array $filters, int $page, int $limit, ?string $sort = null, ?string $order = null): array;

    public function countByFilters(array $filters): int;

    public function save(Series $playlist): void;

    public function delete(Series $playlist): void;

    public function countMultimediaObjects(string $playlistId): int;
}
