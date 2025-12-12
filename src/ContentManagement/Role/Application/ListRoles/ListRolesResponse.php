<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\ListRoles;

final readonly class ListRolesResponse
{
    public function __construct(
        public readonly iterable $roles,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit
    ) {}
}
