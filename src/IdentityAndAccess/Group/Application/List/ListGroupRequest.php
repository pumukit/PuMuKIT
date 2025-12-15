<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\List;

final class ListGroupRequest
{
    public function __construct(
        public array $filters = [],
        public int $page = 1,
        public int $limit = 10,
        public string $sort = 'key',
        public string $order = 'asc'
    ) {}
}
