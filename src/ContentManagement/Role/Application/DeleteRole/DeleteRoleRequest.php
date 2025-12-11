<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\DeleteRole;

final readonly class DeleteRoleRequest
{
    public function __construct(
        public string $id
    ) {}
}
