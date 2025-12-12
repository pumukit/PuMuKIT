<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\UpdateRole;

use Pumukit\SchemaBundle\Document\Role;

final class UpdateRoleResponse
{
    public function __construct(
        public Role $role
    ) {}
}
