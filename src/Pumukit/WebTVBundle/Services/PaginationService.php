<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class PaginationService.
 */
class PaginationService
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @param Builder $objects
     * @param string  $page
     * @param int     $limit
     *
     * @throws \Exception
     *
     * @return mixed|Pagerfanta
     */
    public function createDoctrineODMMongoDBAdapter(Builder $objects, $page, $limit = 0)
    {
        if (0 === $limit) {
            try {
                return $objects->getQuery()->execute();
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }
        }
        $adapter = new DoctrineODMMongoDBAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    /**
     * @param array  $objects
     * @param string $page
     * @param int    $limit
     *
     * @return Pagerfanta
     */
    public function createArrayAdapter(array $objects, $page, $limit = 0)
    {
        $adapter = new ArrayAdapter($objects);

        return $this->generatePager($adapter, $page, $limit);
    }

    /**
     * @param AdapterInterface $adapter
     * @param string           $page
     * @param int              $limit
     *
     * @return Pagerfanta
     */
    private function generatePager(AdapterInterface $adapter, $page, $limit)
    {
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($limit);
        $pager->setNormalizeOutOfRangePages(true);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
