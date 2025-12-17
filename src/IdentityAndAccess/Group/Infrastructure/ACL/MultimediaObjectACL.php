<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\ACL;

use App\ContentManagement\MultimediaObject\Infrastructure\Query\MultimediaObjectReadService;
use App\IdentityAndAccess\Group\Domain\Query\MultimediaObjectQueryInterface;
use App\IdentityAndAccess\Group\Domain\ValueObject\GroupId;

final class MultimediaObjectACL implements MultimediaObjectQueryInterface
{
    public function __construct(private readonly MultimediaObjectReadService $readService) {}

    public function findPaginatedByGroupId(GroupId $groupId, int $page, int $limit, string $sort, string $order): array
    {
        return $this->readService->findPaginatedByGroupId($groupId->toString(), $page, $limit, $sort, $order);
    }

    public function countByGroupId(GroupId $groupId): int
    {
        return $this->readService->countByGroupId($groupId->toString());
    }
}
