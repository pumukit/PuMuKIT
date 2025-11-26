<?php

declare(strict_types=1);

namespace App\Series\Domain\Repository;

use Pumukit\SchemaBundle\Document\SeriesType;

interface SeriesTypeRepositoryInterface
{
    public function find(string $id): ?SeriesType;

    public function findAll(): array;
}

