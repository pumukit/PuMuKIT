<?php

namespace App\IdentityAndAccess\Group\Application\List;

final class ListGroupResponse
{
    public function __construct(
        public array $groups,
        public int $total
    ) {}
}
