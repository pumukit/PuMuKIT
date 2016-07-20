<?php

namespace Pumukit\SchemaBundle\Security;

use Symfony\Component\Security\Core\Role\RoleHierarchy as SymfonyRoleHierarchy;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Services\PermissionService;

class RoleHierarchy extends SymfonyRoleHierarchy
{
    public function __construct(array $hierarchy, PermissionService $permissionService)
    {
        if (isset($hierarchy['ROLE_SUPER_ADMIN'])) {
            $hierarchy['ROLE_SUPER_ADMIN'][] = PermissionProfile::SCOPE_GLOBAL;
            foreach ($permissionService->getPermissionsForSuperAdmin() as $permission) {
                $hierarchy['ROLE_SUPER_ADMIN'][] = $permission;
            }
        }

        parent::__construct($hierarchy);
    }
}