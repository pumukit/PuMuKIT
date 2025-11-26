<?php

declare(strict_types=1);

namespace App\Series\Domain\Repository;

use Pumukit\SchemaBundle\Document\SeriesStyle;

interface SeriesStyleRepositoryInterface
{
    public function find(string $id): ?SeriesStyle;

    public function findAll(): array;
}

