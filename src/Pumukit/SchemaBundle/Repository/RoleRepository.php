<?php

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Pumukit\SchemaBundle\Document\Role;

/**
 * RoleRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RoleRepository extends DocumentRepository
{
    public function findOneByCod(string $cod): ?Role
    {
        return $this->findOneBy(['cod' => $cod]);
    }

    /**
     * Find all roles in the repository order by rank.
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder()
            ->sort('rank', -1)
            ->getQuery()
            ->execute()
        ;
    }
}
