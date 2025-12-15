<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\Find;

final class FindGroupRequest
{
    public function __construct(
        public string $id
    ) {}
}
