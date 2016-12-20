<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Document\PermissionProfile;

class PermissionService
{
    private $repo;
    private $externalPermissions;
    private $allPermissions;

    /**
     * Constructor.
     *
     * @param array $externalPermissions
     */
    public function __construct(DocumentManager $documentManager, array $externalPermissions = array())
    {
        $this->repo = $documentManager->getRepository('PumukitSchemaBundle:Tag');
        $this->externalPermissions = $externalPermissions;
        $this->allPermissions = $this->buildAllDependencies();
    }

    /**
     * Get external permissions.
     */
    public function getExternalPermissions()
    {
        return $this->externalPermissions;
    }

    /**
     * Get local permissions.
     */
    public function getLocalPermissions()
    {
        return Permission::$permissionDescription;
    }

    /**
     * Get local permissions.
     */
    public function getPubTagsPermissions()
    {
        $return = array();
        $tag = $this->repo->findOneByCod('PUBCHANNELS');
        if (!$tag) {
            return $return;
        }

        foreach ($tag->getChildren() as $pubchannel) {
            $return[Permission::getRoleTagDisableForPubChannel($pubchannel->getCod())] = array(
                'description' => 'Publication channel "'.$pubchannel->getTitle().'" disabled',
                'dependencies' => array(
                    PermissionProfile::SCOPE_GLOBAL => array(),
                    PermissionProfile::SCOPE_PERSONAL => array(),
                ),
            );

            $return[Permission::getRoleTagDefaultForPubChannel($pubchannel->getCod())] = array(
                'description' => 'Publication channel "'.$pubchannel->getTitle().'" activated by default',
                'dependencies' => array(
                    PermissionProfile::SCOPE_GLOBAL => array(),
                    PermissionProfile::SCOPE_PERSONAL => array(),
                ),
            );
        }

        return $return;
    }

    /**
     * Get all permissions.
     */
    public function getAllPermissions()
    {
        return array_map(function ($a) {
            return $a['description'];
        }, $this->allPermissions);
    }

    /**
     * Get permissions for super admin (see RoleHierarchy).
     */
    public function getPermissionsForSuperAdmin()
    {
        $permissions = array();
        foreach ($this->externalPermissions as $perm) {
            $permissions[] = $perm['role'];
        }

        foreach ($this->getLocalPermissions() as $role => $perm) {
            $permissions[] = $role;
        }

        return $permissions;
    }

    /**
     * Get all dependencies.
     */
    public function getAllDependencies()
    {
        return array_map(function ($a) {
            return $a['dependencies'];
        }, $this->allPermissions);
    }

    /**
     * Check if exist a permission.
     */
    public function exists($permission)
    {
        return array_key_exists($permission, $this->allPermissions);
    }

    /**
     * Build all permissions.
     */
    private function buildAllPermissions()
    {
        //Empty 'dependencies' to add to a permission without them
        $defaultDeps = array(
            PermissionProfile::SCOPE_GLOBAL => array(),
            PermissionProfile::SCOPE_PERSONAL => array(),
        );
        $allPermissions = $this->getLocalPermissions() + $this->getPubTagsPermissions();
        foreach ($this->externalPermissions as $externalPermission) {
            if (array_key_exists($externalPermission['role'], $allPermissions)) {
                throw new \RuntimeException(sprintf('The permission with role \'%s\' is duplicated. Please check the configuration.', $externalPermission['role']));
            }
            if (false === strpos($externalPermission['role'], 'ROLE_')) {
                throw new \UnexpectedValueException('Invalid permission: "'.$externalPermission['role'].'". Permission must start with "ROLE_".');
            }

            $dependencies = $defaultDeps;
            if (isset($externalPermission['dependencies'])) {
                $dependencies[PermissionProfile::SCOPE_GLOBAL] = $externalPermission['dependencies']['global'];
                $dependencies[PermissionProfile::SCOPE_PERSONAL] = $externalPermission['dependencies']['personal'];
            }

            $allPermissions[$externalPermission['role']] = array(
                'description' => $externalPermission['description'],
                'dependencies' => $dependencies,
            );
        }

        return $allPermissions;
    }

    /**
     * Build all dependencies.
     */
    private function buildAllDependencies()
    {
        $allPermissions = $this->buildAllPermissions();
        foreach ($allPermissions as $role => $permission) {
            foreach ($permission['dependencies'] as $scope => $dependencies) {
                $allPermissions[$role]['dependencies'][$scope] = $this->buildDependenciesByScope($role, $scope, $allPermissions);
            }
        }

        return $allPermissions;
    }

    /**
     * Returns permissions dependencies by scope.
     *
     * @param string $permission
     * @param string $scope
     * @param array  $allPermissions
     */
    private function buildDependenciesByScope($permission, $scope, array $allPermissions)
    {
        if (!array_key_exists($permission, $allPermissions)) {
            throw new \InvalidArgumentException("The permission with role '$permission' does not exist in the configuration");
        }
        if (!in_array($scope, array(PermissionProfile::SCOPE_GLOBAL, PermissionProfile::SCOPE_PERSONAL))) {
            throw new \InvalidArgumentException("The scope '$scope' is not a valid scope (SCOPE_GLOBAL or SCOPE_PERSONAL)");
        }

        $dependencies = $allPermissions[$permission]['dependencies'][$scope];
        $dependencies = array_diff($dependencies, array($permission));

        reset($dependencies);
        while (($elem = each($dependencies)) !== false) {
            if (!array_key_exists($elem['value'], $allPermissions)) {
                throw new \InvalidArgumentException(sprintf('The permission with role \'%s\' does not exist in the configuration', $elem['value']));
            }
            foreach ($allPermissions[$elem['value']]['dependencies'][$scope] as $newDep) {
                if ($newDep != $permission && !in_array($newDep, $dependencies)) {
                    $dependencies[] = $newDep;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Returns dependable permissions.
     *
     * It returns all permissions that have the param $permission as a 'dependency'
     *
     * @param string $permission
     * @param string $scope
     */
    public function getDependablesByScope($permission, $scope)
    {
        if (!array_key_exists($permission, $this->allPermissions)) {
            throw new \InvalidArgumentException("The permission with role '$permission' does not exist in the configuration");
        }
        if (!in_array($scope, array(PermissionProfile::SCOPE_GLOBAL, PermissionProfile::SCOPE_PERSONAL))) {
            throw new \InvalidArgumentException("The scope '$scope' is not a valid scope (SCOPE_GLOBAL or SCOPE_PERSONAL)");
        }
        $dependables = array_filter(
            $this->allPermissions,
            function ($a) use ($permission, $scope) {
                return in_array($permission, $a['dependencies'][$scope]);
            }
        );

        $dependables = array_keys($dependables);

        return $dependables;
    }

    /**
     * Returns a permission dependencies.
     *
     * It returns all permissions that have the $permission as a 'dependency'
     *
     * @param string $permission
     * @param string $scope
     */
    public function getDependenciesByScope($permission, $scope)
    {
        if (!array_key_exists($permission, $this->allPermissions)) {
            throw new \InvalidArgumentException("The permission with role '$permission' does not exist in the configuration");
        }
        if (!in_array($scope, array(PermissionProfile::SCOPE_GLOBAL, PermissionProfile::SCOPE_PERSONAL))) {
            throw new \InvalidArgumentException("The scope '$scope' is not a valid scope (SCOPE_GLOBAL or SCOPE_PERSONAL)");
        }

        return $this->allPermissions[$permission]['dependencies'][$scope];
    }
}
