<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class LiveRepository extends DocumentRepository
{
    public function createAbcSortQueryBuilder(string $locale = 'en'): Builder
    {
        return $this->createQueryBuilder()->sort('name.'.$locale, 1);
    }
}
