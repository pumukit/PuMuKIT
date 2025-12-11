<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\List;

final class ListUserRequest
{
    public function __construct(
        public readonly ?int $page = 1,
        public readonly ?int $limit = 20,
        public readonly array $filters = [],
        public readonly string $sort = 'username',
        public readonly string $order = 'asc'
    ) {}
}
