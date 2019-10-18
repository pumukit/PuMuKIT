<?php

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\User;

/**
 * UserRepository.
 */
class UserRepository extends DocumentRepository
{
    /**
     * Find all people belonging to any of the given groups.
     *
     * @param array $groups
     *
     * @return ArrayCollection
     */
    public function findUsersInAnyGroups($groups)
    {
        $userRepo = $this->getDocumentManager()->getRepository(User::class);

        $groupsIds = array_map(function ($group) {
            return new ObjectId($group->getId());
        }, $groups);

        return $userRepo
            ->createQueryBuilder()
            ->field('groups')
            ->in($groupsIds)
            ->getQuery()
            ->execute()->toArray();
    }
}
