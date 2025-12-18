<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\Search;

final class SearchUsersByCriteriaRequest
{
    public function __construct(
        public array $filters,
        public ?string $orderBy = null,
        public ?string $order = null,
        public ?int $limit = null,
        public ?int $offset = null
    ) {}
}
