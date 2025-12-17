<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Domain\Repository;

use App\IdentityAndAccess\Group\Domain\ValueObject\GroupId;

interface GroupUserRepositoryInterface
{
    public function findPaginatedByGroupId(
        GroupId $groupId,
        int $page,
        int $limit,
        string $sort,
        string $order
    ): array;

    public function countUsersByGroupId(GroupId $groupId): int;
}
