<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;

class UserRepository extends DocumentRepository
{
    public function findUsersInAnyGroups(array $groups)
    {
        $userRepo = $this->getDocumentManager()->getRepository(User::class);
        $groupsIds = array_map(static function (Group $group) {
            return new ObjectId($group->getId());
        }, $groups);

        return $userRepo
            ->createQueryBuilder()
            ->field('groups')
            ->in($groupsIds)
            ->getQuery()
            ->execute()
        ;
    }
}
