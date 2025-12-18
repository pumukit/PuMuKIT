<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Infrastructure\Query;

use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;

final class MultimediaObjectReadService
{
    public function __construct(private readonly MultimediaObjectRepositoryInterface $repository) {}

    public function findPaginatedByGroupId(string $groupId, int $page, int $limit, string $sort, string $order): array
    {
        return $this->repository->findPaginatedByGroupId($groupId, $page, $limit, $sort, $order);
    }

    public function countByGroupId(string $groupId): int
    {
        return $this->repository->countByGroupId($groupId);
    }
}
