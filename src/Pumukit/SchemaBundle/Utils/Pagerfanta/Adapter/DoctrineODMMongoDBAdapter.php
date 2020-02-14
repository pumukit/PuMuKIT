<?php

namespace Pumukit\SchemaBundle\Utils\Pagerfanta\Adapter;

use Doctrine\ODM\MongoDB\Query\Builder;
use Pagerfanta\Adapter\AdapterInterface;

class DoctrineODMMongoDBAdapter implements AdapterInterface
{
    private $queryBuilder;
    private $query;

    public function __construct(Builder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getQueryBuilder(): Builder
    {
        return $this->queryBuilder;
    }

    public function getNbResults()
    {
        if ($this->query) {
            //Take adventage of Mongo re-using the complete query from getSlice.
            return count($this->query);
        }

        return $this->queryBuilder->count()->getQuery()->execute();
    }

    public function getSlice($offset, $length)
    {
        if (!$this->query) {
            $this->query = $this->queryBuilder
                ->limit($length)
                ->skip($offset)
                ->getQuery()
                ->execute()
            ;
        }

        return $this->query;
    }
}
