<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\ACL;

use App\IdentityAndAccess\Group\Domain\ValueObject\GroupId;
use App\IdentityAndAccess\Group\Domain\Query\MultimediaObjectQueryInterface;
use App\ContentManagement\MultimediaObject\Infrastructure\Query\MultimediaObjectReadService;

final class MultimediaObjectACL implements MultimediaObjectQueryInterface
{
    public function __construct(private readonly MultimediaObjectReadService $readService)
    {
    }

    public function findIdsAndTitlesByGroupId(GroupId $groupId): array
    {
        return $this->readService->findIdsAndTitlesByGroupId($groupId);
    }
}
