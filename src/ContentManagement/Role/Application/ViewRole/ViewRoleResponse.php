<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\ViewRole;

use Pumukit\SchemaBundle\Document\Role;

final readonly class ViewRoleResponse
{
    public function __construct(
        public Role $role
    ) {}
}

