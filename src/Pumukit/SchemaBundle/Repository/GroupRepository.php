<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * GroupRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GroupRepository extends DocumentRepository
{
    /**
     * Find groups not in
     * the given array.
     *
     * @param array $ids
     *
     * @return mixed
     */
    public function findByIdNotIn($ids = [])
    {
        return $this->createQueryBuilder()
            ->field('_id')->notIn($ids)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Find groups not in
     * the given array but
     * in the total of groups
     * given.
     *
     * @param array $ids
     * @param array $total
     *
     * @return mixed
     */
    public function findByIdNotInOf($ids = [], $total = [])
    {
        return $this->createQueryBuilder()
            ->field('_id')->in($total)
            ->field('_id')->notIn($ids)
            ->getQuery()
            ->execute()
        ;
    }
}
