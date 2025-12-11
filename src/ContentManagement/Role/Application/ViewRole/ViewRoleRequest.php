<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\ViewRole;

final readonly class ViewRoleRequest
{
    public function __construct(
        public string $id
    ) {}
}
