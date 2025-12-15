<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\DeleteRole;

final class DeleteRoleResponse
{
    public function __construct(
        public string $id
    ) {}
}
