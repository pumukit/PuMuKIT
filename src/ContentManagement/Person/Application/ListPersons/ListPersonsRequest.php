<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\ListPersons;

final readonly class ListPersonsRequest
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 10,
        public readonly string $sort = 'email',
        public readonly string $order = 'desc',
        public readonly array $filters = []
    ) {}
}
