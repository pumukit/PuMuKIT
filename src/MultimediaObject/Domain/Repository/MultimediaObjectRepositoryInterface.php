<?php

namespace App\MultimediaObject\Domain\Repository;

use Pumukit\SchemaBundle\Document\MultimediaObject;

interface MultimediaObjectRepositoryInterface
{
    public function find(string $id): ?object;

    public function findBySeriesId(string $seriesId): iterable;

    public function save(MultimediaObject $multimediaObject): void;

    public function delete(MultimediaObject $multimediaObject): void;
}
