<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Document\PermissionProfile;

class PermissionService
{
    private $externalPermissions;
    private $allPermissions;

    /**
     * Constructor
     *
     * @param array $externalPermissions
     */
    public function __construct(array $externalPermissions = array())
    {
        $this->externalPermissions = $externalPermissions;
        $this->allPermissions = $this->buildAllPermissions();
    }

    /**
     * Get external permissions
     */
    public function getExternalPermissions()
    {
        return $this->externalPermissions;
    }

    /**
     * Get local permissions
     */
    public function getLocalPermissions()
    {
        return Permission::$permissionDescription;
    }

    /**
     * Get local dependencies
     */
    public function getLocalDependencies()
    {
        return Permission::$permissionDependencies;
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions()
    {
        return array_map(function($a){
            return $a['description'];
        },$this->allPermissions);
    }


    /**
     * Get all dependencies
     */
    public function getAllDependencies()
    {
        return array_map(function($a){
            return $a['dependencies'];
        }, $this->allPermissions);
    }


    /**
     * Check if exist a permission
     */
    public function exists($permission)
    {
        return array_key_exists($permission, $this->allPermissions);
    }


    /**
     * Build all permissions
     */
    private function buildAllPermissions()
    {
        //Empty 'dependencies' to add to a permission without them
        $defaultDeps = array(
            PermissionProfile::SCOPE_GLOBAL => array(),
            PermissionProfile::SCOPE_PERSONAL => array()
        );
        $allPermissions = $this->getLocalPermissions();
        foreach ($this->externalPermissions as $externalPermission) {
            if (false === strpos($externalPermission['role'], 'ROLE_')) {
                throw new \Exception('Invalid permission: "'.$externalPermission['role'].'". Permission must start with "ROLE_".');
            }

            $dependencies = $defaultDeps;            
            if(isset($externalPermission['dependencies']) && !isset($externalPermission['dependencies']['global'])){
                var_dump($externalPermission);exit();
            }
            if(isset($externalPermission['dependencies'])) {
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
     * Returns permissions dependent of a given permission by scope 
     *
     * @param string $permission
     * @param string $scope
     */
    public function getDependenciesByScope($permission, $scope)
    {
        if(!array_key_exists($permission, $this->allPermissions)) {
            return array();
        }
        $dependencies = $this->allPermissions[$permission]['dependencies'][$scope];
        $dependencies = array_diff($dependencies, array($permission));

        reset($dependencies);
        while(($elem = each($dependencies)) !== false) {
            foreach($this->allPermissions[$elem['value']]['dependencies'][$scope] as $newDep) {
                if($newDep != $permission && !in_array($newDep, $dependencies)) {
                    $dependencies[] = $newDep;
                }
            }
        }
        return $dependencies;
    }
}
