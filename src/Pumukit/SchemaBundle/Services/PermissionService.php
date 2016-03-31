<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Security\Permission;

class PermissionService
{
    private $externalPermissions;
    private $allPermissions;
    private $allDependencies;

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
            'global' => array(),
            'personal' => array()
        );
        $allPermissions = $this->getLocalPermissions();
        foreach ($this->externalPermissions as $externalPermission) {
            if (false === strpos($externalPermission['role'], 'ROLE_')) {
                throw new \Exception('Invalid permission: "'.$externalPermission['role'].'". Permission must start with "ROLE_".');
            }
            $allPermissions[$externalPermission['role']] = array(
                'description' => $externalPermission['description'],
                'dependencies' => isset($externalPermission['dependencies']) ? $externalPermission['dependencies'] : $defaultDeps,
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
        return $this->allPermissions;
        $dependencies = array();
        if(!array_key_exists($permission, $this->allDependencies)) {
            return array();
        }
        $dependencies = $this->allDependencies[$permission];
        $ref = &$dependencies;
        foreach($ref as $dep) {
            $ref = array_merge($dependencies, $this->allDependencies[$dep]);
        }
        return $dependencies;
    }
}
