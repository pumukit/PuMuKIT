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
    public function createDoctrineODMMongoDBAdapter(Builder $objects, $page = 1, $limit = 10): Pagerfanta
    {
        [$page, $limit] = $this->validatePagerValues($page, $limit);

        $adapter = new DoctrineODMMongoDBAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    public function createArrayAdapter(array $objects, $page = 1, $limit = 10): Pagerfanta
    {
        [$page, $limit] = $this->validatePagerValues($page, $limit);

        $adapter = new ArrayAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    public function createDoctrineCollectionAdapter($objects, $page = 1, $limit = 10): Pagerfanta
    {
        [$page, $limit] = $this->validatePagerValues($page, $limit);

        $adapter = new DoctrineCollectionAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    private function generatePager(AdapterInterface $adapter, int $page = 1, int $limit = 10): Pagerfanta
    {
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($page);
        $pager->setNormalizeOutOfRangePages(true);
        $pager->setCurrentPage($limit);

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
