<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\ListRoles;

final readonly class ListRolesRequest
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 10,
        public readonly string $sort = 'rank',
        public readonly string $order = 'desc',
        public readonly array $filters = []
    ) {}
}
