<?php

namespace Pumukit\CoreBundle\Services;

use Doctrine\ODM\MongoDB\Query\Builder;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;

class PaginationService
{
    private const DEFAULT_MAX_ELEMENTS_PER_PAGE = 10;
    private const DEFAULT_PAGE = 1;

    public function createDoctrineODMMongoDBAdapter(Builder $objects, int $page = self::DEFAULT_PAGE, int $limit = self::DEFAULT_MAX_ELEMENTS_PER_PAGE): Pagerfanta
    {
        $adapter = new DoctrineODMMongoDBAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    public function createArrayAdapter(array $objects, int $page = self::DEFAULT_PAGE, int $limit = self::DEFAULT_MAX_ELEMENTS_PER_PAGE): Pagerfanta
    {
        $adapter = new ArrayAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    public function createDoctrineCollectionAdapter($objects, int $page = self::DEFAULT_PAGE, int $limit = self::DEFAULT_MAX_ELEMENTS_PER_PAGE): Pagerfanta
    {
        $adapter = new DoctrineCollectionAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    public function createFixedAdapter($total, array $objects, int $page = self::DEFAULT_PAGE, int $limit = self::DEFAULT_MAX_ELEMENTS_PER_PAGE): Pagerfanta
    {
        $adapter = new FixedAdapter($total, $objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    private function generatePager(AdapterInterface $adapter, int $page, int $limit): Pagerfanta
    {
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($limit);
        $pager->setNormalizeOutOfRangePages(true);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
