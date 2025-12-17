<?php

namespace App\IdentityAndAccess\Group\Infrastructure\Persistence;

use App\IdentityAndAccess\Group\Domain\Repository\GroupUserRepositoryInterface;
use App\IdentityAndAccess\Group\Domain\ValueObject\GroupId;
use App\IdentityAndAccess\Group\Domain\ValueObject\GroupUserListItem;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use Pumukit\SchemaBundle\Document\User;

class DoctrineGroupUserRepository implements GroupUserRepositoryInterface
{
    public function __construct(private readonly DoctrineObjectManager $objectManager) {}

    public function findPaginatedByGroupId(
        GroupId $groupId,
        int $page,
        int $limit,
        string $sort,
        string $order
    ): array {
        $skip = ($page - 1) * $limit;

        $documents = $this->objectManager->getDocumentManager()->createQueryBuilder(User::class)
            ->field('groups')->equals($groupId->value())
            ->sort($sort, 'asc' === $order ? 1 : -1)
            ->limit($limit)
            ->skip($skip)
            ->getQuery()
            ->execute()
        ;

        return array_map(
            fn (User $doc) => new GroupUserListItem(
                $doc->getId(),
                $doc->getUsername(),
                $doc->getEmail(),
                $doc->getFullName()
            ),
            iterator_to_array($documents)
        );
    }

    public function countUsersByGroupId(GroupId $groupId): int
    {
        return $this->objectManager->getDocumentManager()->createQueryBuilder(User::class)
            ->field('groups')->equals($groupId->value())
            ->count()
            ->getQuery()
            ->execute()
        ;
    }
}
