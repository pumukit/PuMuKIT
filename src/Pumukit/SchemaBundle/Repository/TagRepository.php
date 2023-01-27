<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * TagRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TagRepository extends DocumentRepository
{
    public function findOneByCod(string $cod): ?Tag
    {
        return $this->findOneBy(['cod' => $cod]);
    }
}
