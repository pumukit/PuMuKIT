<?php

namespace Pumukit\SchemaBundle\Security;

use Symfony\Component\Security\Core\Role\RoleHierarchy as SymfonyRoleHierarchy;
use Pumukit\SchemaBundle\Document\PermissionProfile;

class RoleHierarchy extends SymfonyRoleHierarchy
{
    public function __construct(array $hierarchy)
    {
        if (isset($hierarchy['ROLE_SUPER_ADMIN'])) {
            $hierarchy['ROLE_SUPER_ADMIN'][] = PermissionProfile::SCOPE_GLOBAL;
            foreach (Permission::$permissionDescription as $permission => $description) {
                $hierarchy['ROLE_SUPER_ADMIN'][] = $permission;
            }
        }

        parent::__construct($hierarchy);
    }
}