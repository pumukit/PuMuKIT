<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\Persistence\MongoDb;

use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filter;
use Doctrine\ODM\MongoDB\Query\Builder;

final class MongoDbCriteriaConverter
{
    public function convert(Builder $queryBuilder, Criteria $criteria): Builder
    {
        foreach ($criteria->filters() as $filter) {
            $this->applyFilter($queryBuilder, $filter);
        }

        if ($criteria->order()) {
            $queryBuilder->sort(
                $criteria->order()->orderBy(),
                $criteria->order()->orderType()
            );
        }

        if ($criteria->limit()) {
            $queryBuilder->limit($criteria->limit());
        }

        if ($criteria->offset()) {
            $queryBuilder->skip($criteria->offset());
        }

        return $queryBuilder;
    }

    private function applyFilter(Builder $qb, Filter $filter): void
    {
        $field = $filter->field();
        $value = $filter->value();

        switch ($filter->operator()) {
            case '=':
                $qb->field($field)->equals($value);
                break;
            case 'IN':
                $qb->field($field)->in((array) $value);
                break;
            case 'CONTAINS':
                $qb->field($field)->equals(new \MongoDB\BSON\Regex((string)$value, 'i'));
                break;
        }
    }
}
