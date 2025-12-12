<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\CreateRole;

use Pumukit\SchemaBundle\Document\Role;

final class CreateRoleResponse
{
    public function __construct(
        public Role $role
    ) {}
}
