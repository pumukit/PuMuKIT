<?php

namespace Pumukit\SchemaBundle\Utils\Pagerfanta\Adapter;

use Doctrine\ODM\MongoDB\Query\Builder;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * DoctrineODMMongoDBAdapter.
 */
class DoctrineODMMongoDBAdapter implements AdapterInterface
{
    private $queryBuilder;
    private $query;

    /**
     * Constructor.
     *
     * @param Builder $queryBuilder A DoctrineMongo query builder
     */
    public function __construct(Builder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Returns the query builder.
     *
     * @return Builder The query builder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        $elements = $this->query ?? $this->queryBuilder->getQuery();

        return count($elements->toArray());
    }

    /**
     * {@inheritdoc}
     */
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
