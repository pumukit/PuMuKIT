<?php

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentRepository;

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
        $userRepo = $this->getDocumentManager()->getRepository('PumukitSchemaBundle:User');
        //Why is there a need to to this? Can't I just pass the array of groups?
        $groupsIds = [];
        foreach ($groups as $group) {
            $groupsIds[] = new \MongoId($group->getId());
        }
        $users = $userRepo
                ->createQueryBuilder()
                ->field('groups')
                ->in($groupsIds)
                ->getQuery()
                ->execute()->toArray();

        return $users;
    }
}
