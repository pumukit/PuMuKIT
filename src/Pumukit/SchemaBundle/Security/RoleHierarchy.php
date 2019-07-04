<?php

namespace Pumukit\SchemaBundle\Security;

use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Services\PermissionService;
use Symfony\Component\Security\Core\Role\RoleHierarchy as SymfonyRoleHierarchy;

class RoleHierarchy extends SymfonyRoleHierarchy
{
    public function __construct(array $hierarchy, PermissionService $permissionService)
    {
        if (isset($hierarchy['ROLE_SUPER_ADMIN'])) {
            $hierarchy['ROLE_SUPER_ADMIN'][] = PermissionProfile::SCOPE_GLOBAL;
            foreach ($permissionService->getPermissionsForSuperAdmin() as $permission) {
                if (false === stripos($permission, 'DISABLED')) {
                    $hierarchy['ROLE_SUPER_ADMIN'][] = $permission;
                }
            }
        }

        parent::__construct($hierarchy);
    }
}
