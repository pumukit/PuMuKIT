<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Security\Core\User\UserInterface;

class UserRepository extends DocumentRepository
{
    public function userExists(array $criteria)
    {
        return $this->dm->getRepository(User::class)->findOneBy($criteria);
    }

    public function findUsersInAnyGroups(array $groups)
    {
        $userRepo = $this->dm->getRepository(User::class);
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

    public function save(UserInterface $user): void
    {
        $this->persist($user);
        $this->dm->flush();
    }

    public function persist(UserInterface $user): void
    {
        $this->dm->persist($user);
    }
}
