<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\Delete;

final class DeleteGroupRequest
{
    public function __construct(
        public string $id
    ) {}
}
