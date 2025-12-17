<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Domain\Query;

use App\IdentityAndAccess\Group\Domain\ValueObject\GroupId;

interface MultimediaObjectQueryInterface
{
    public function findPaginatedByGroupId(
        GroupId $groupId,
        int $page,
        int $limit,
        string $sort,
        string $order
    ): array;

    public function countByGroupId(GroupId $groupId): int;
}
