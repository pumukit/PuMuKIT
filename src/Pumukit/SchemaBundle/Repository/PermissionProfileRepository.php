<?php

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Pumukit\SchemaBundle\Document\PermissionProfile;

class PermissionProfileRepository extends DocumentRepository
{
    public function changeDefault(PermissionProfile $permissionProfile, bool $default = true): void
    {
        $this->createQueryBuilder()
            ->field('name')->notEqual($permissionProfile->getName())
            ->updateMany()
            ->field('default')->set(!$default)
            ->field('default')->equals($default)
            ->getQuery()
            ->execute()
        ;
    }

    public function findDefaultCandidate(int $totalPermissions = 0)
    {
        $count = 0;
        $size = -1;
        do {
            if ($count > 0) {
                break;
            }
            ++$size;
            $count = $this->createQueryBuilder()
                ->field('permissions')->size($size)
                ->count()
                ->getQuery()
                ->execute()
            ;
        } while ($size <= $totalPermissions);

        if ($count > 0) {
            return $this->createQueryBuilder()
                ->field('permissions')->size($size)
                ->getQuery()
                ->getSingleResult()
            ;
        }

        return null;
    }
}
