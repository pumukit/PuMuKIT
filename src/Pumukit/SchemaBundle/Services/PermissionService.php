<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Security\Permission;

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
     * Get all permissions
     */
    public function getAllPermissions()
    {
        return $this->allPermissions;
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
        $allPermissions = $this->getLocalPermissions();
        foreach ($this->externalPermissions as $externalPermission) {
            if (false === strpos($externalPermission['role'], 'ROLE_')) {
                throw new \Exception('Invalid permission: "'.$externalPermission['role'].'". Permission must start with "ROLE_".');
            }
            $allPermissions[$externalPermission['role']] = $externalPermission['description'];
        }

        return $allPermissions;
    }
}