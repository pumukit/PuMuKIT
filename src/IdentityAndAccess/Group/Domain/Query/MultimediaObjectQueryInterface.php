<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Domain\Query;

use App\IdentityAndAccess\Group\Domain\ValueObject\GroupId;

interface MultimediaObjectQueryInterface
{
    public function findIdsAndTitlesByGroupId(GroupId $groupId): array;
}
