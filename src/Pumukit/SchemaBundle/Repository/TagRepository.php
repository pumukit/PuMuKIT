<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Repository;

use Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * This class override MaterializedPathRepository final class to add new method and have other methods.
 */
class TagRepository extends MaterializedPathRepository
{
    public function findOneByCod(string $cod): ?Tag
    {
        return $this->findOneBy(['cod' => $cod]);
    }
}
