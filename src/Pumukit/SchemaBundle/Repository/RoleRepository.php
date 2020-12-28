<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Pumukit\SchemaBundle\Document\Role;

class RoleRepository extends DocumentRepository
{
    public function findOneByCod(string $cod): ?Role
    {
        return $this->findOneBy(['cod' => $cod]);
    }

    public function findAll(): array
    {
        return iterator_to_array($this->createQueryBuilder()->sort('rank', -1)->getQuery()->execute());
    }
}
