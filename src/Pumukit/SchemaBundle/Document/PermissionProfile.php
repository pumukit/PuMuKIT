<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\PermissionProfileRepository")
 */
class PermissionProfile
{
    const SCOPE_GLOBAL = 'ROLE_SCOPE_GLOBAL';
    const SCOPE_PERSONAL = 'ROLE_SCOPE_PERSONAL';
    const SCOPE_NONE = 'ROLE_SCOPE_NONE';

    public static $scopeDescription = [
        self::SCOPE_GLOBAL => 'Global Scope',
        self::SCOPE_PERSONAL => 'Personal Scope',
        self::SCOPE_NONE => 'No Scope',
    ];

    /**
     * @var string
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * @MongoDB\UniqueIndex(order="asc")
     */
    private $name = '';

    /**
     * @var array
     *
     * @MongoDB\Field(type="collection")
     */
    private $permissions = [];

    /**
     * @var bool
     *
     * @MongoDB\Field(type="boolean")
     */
    private $system = false;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="boolean")
     */
    private $default = false;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $scope = self::SCOPE_PERSONAL;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     * @Gedmo\SortablePosition
     */
    private $rank;

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set permissions.
     *
     * @param array $permissions
     */
    public function setPermissions(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Get permissions.
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Add permission.
     *
     * @param string $permission
     */
    public function addPermission($permission)
    {
        $this->permissions[] = $permission;

        return $this->permissions = array_unique($this->permissions);
    }

    /**
     * Remove permission.
     *
     * @param string $permission
     *
     * @return bool TRUE if this PermissionProfile contains the specified permission, FALSE otherwise
     */
    public function removePermission($permission)
    {
        $permission = array_search($permission, $this->permissions, true);

        if (false !== $permission) {
            unset($this->permissions[$permission]);

            return true;
        }

        return false;
    }

    /**
     * Contains permission.
     *
     * @param string $permission
     *
     * @return bool TRUE if this PermissionProfile contains the specified permission, FALSE otherwise
     */
    public function containsPermission($permission)
    {
        return in_array($permission, $this->permissions, true);
    }

    /**
     * Contains all permissions.
     *
     * @param array $permissions
     *
     * @return bool TRUE if this PermissionProfile contains all permissions, FALSE otherwise
     */
    public function containsAllPermissions(array $permissions)
    {
        return count(array_intersect($permissions, $this->permissions)) === count($permissions);
    }

    /**
     * Contains any permissions.
     *
     * @param array $permissions
     *
     * @return bool TRUE if this PermissionProfile contains any permission of the list, FALSE otherwise
     */
    public function containsAnyPermission(array $permissions)
    {
        return 0 != count(array_intersect($permissions, $this->permissions));
    }

    /**
     * Set system.
     *
     * @param bool $system
     */
    public function setSystem($system)
    {
        $this->system = $system;
    }

    /**
     * Get system.
     *
     * @return bool
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * Set default.
     *
     * @param bool $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * Get default.
     *
     * @return bool
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set scope.
     *
     * @param int $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * Get scope.
     *
     * @return int
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set rank.
     *
     * @param int $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    /**
     * Get rank.
     *
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Helper function to know if the
     * PermissionProfile is a global scope.
     *
     * @return bool
     */
    public function isGlobal()
    {
        return self::SCOPE_GLOBAL === $this->getScope();
    }

    /**
     * Helper function to know if the
     * PermissionProfile is a personal scope.
     *
     * @return bool
     */
    public function isPersonal()
    {
        return self::SCOPE_PERSONAL === $this->getScope();
    }

    /**
     * Helper function to know if the
     * PermissionProfile is a none scope.
     *
     * @return bool
     */
    public function isNone()
    {
        return self::SCOPE_NONE === $this->getScope();
    }

    /**
     * To String.
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Is system.
     *
     * @return bool
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * Is default.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }
}
