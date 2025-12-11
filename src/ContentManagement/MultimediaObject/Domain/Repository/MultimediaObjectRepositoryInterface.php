<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Domain\Repository;

use Pumukit\SchemaBundle\Document\MultimediaObject;

interface MultimediaObjectRepositoryInterface
{
    public function find(string $id): ?MultimediaObject;

    public function findAll(int $page = 1, int $limit = 10, ?array $sort = null, ?array $filters = []): iterable;

    public function findById(string $id): mixed;

    public function countAll(?array $filters = []): int;

    public function save(MultimediaObject $multimediaObject): void;

    public function delete(MultimediaObject $multimediaObject): void;
}
