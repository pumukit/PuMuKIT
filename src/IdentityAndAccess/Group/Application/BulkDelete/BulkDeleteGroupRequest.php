<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\BulkDelete;

final class BulkDeleteGroupRequest
{
    public function __construct(
        public array $ids
    ) {}
}
