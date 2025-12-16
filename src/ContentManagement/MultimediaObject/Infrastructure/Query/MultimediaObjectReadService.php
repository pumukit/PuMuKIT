<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Infrastructure\Query;

use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\IdentityAndAccess\Group\Domain\ValueObject\GroupId;

final class MultimediaObjectReadService
{
    public function __construct(private readonly MultimediaObjectRepositoryInterface $repository)
    {
    }

    public function findIdsAndTitlesByGroupId(GroupId $groupId): array
    {
        return $this->repository->findIdsAndTitlesByGroupId($groupId->toString());
    }
}
