<?php

namespace Pumukit\CoreBundle\Services;

use Doctrine\ODM\MongoDB\Query\Builder;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;

class PaginationService
{
    public function createDoctrineODMMongoDBAdapter(Builder $objects, $page = 0, $limit = 0)
    {
        [$page, $limit] = $this->validatePagerValues($page, $limit);

        if (0 === $limit) {
            return $objects->getQuery()->execute();
        }

        $adapter = new DoctrineODMMongoDBAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    public function createArrayAdapter(array $objects, $page = 0, $limit = 0): Pagerfanta
    {
        [$page, $limit] = $this->validatePagerValues($page, $limit);

        $adapter = new ArrayAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    public function createDoctrineCollectionAdapter($objects, $page = 0, $limit = 0): Pagerfanta
    {
        [$page, $limit] = $this->validatePagerValues($page, $limit);

        $adapter = new DoctrineCollectionAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    private function generatePager(AdapterInterface $adapter, int $page = 0, int $limit = 0): Pagerfanta
    {
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($limit);
        $pager->setNormalizeOutOfRangePages(true);
        $pager->setCurrentPage($page);

        return $pager;
    }

    private function validatePagerValues($page, $limit): array
    {
        return [
            (int) $page,
            (int) $limit,
        ];
    }
}
