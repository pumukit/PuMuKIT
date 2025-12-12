<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\List;

final class ListUserRequest
{
    public function __construct(
        public ?int $page = 1,
        public ?int $limit = 20,
        public array $filters = [],
        public string $sort = 'username',
        public string $order = 'asc'
    ) {}
}
