<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\List;

final class ListPermissionProfileRequest
{
    public function __construct(
        public array $filters = [],
        public int $page = 1,
        public int $limit = 10,
        public string $sort = 'name',
        public string $order = 'asc'
    ) {}
}
