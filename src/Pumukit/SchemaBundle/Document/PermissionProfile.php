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

    public static $scopeDescription = array(
                                            self::SCOPE_GLOBAL => 'Global Scope',
                                            self::SCOPE_PERSONAL => 'Personal Scope',
                                            self::SCOPE_NONE => 'No Scope',
                                            );

    /**
     * @var string $id
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string $name
     *
     * @MongoDB\String
     * @MongoDB\UniqueIndex(order="asc")
     */
    private $name = '';

    /**
     * @var array $permissions
     *
     * @MongoDB\Collection
     */
    private $permissions = array();

    /**
     * @var boolean $system
     *
     * @MongoDB\Boolean
     */
    private $system = false;

    /**
     * @var boolean $default
     *
     * @MongoDB\Boolean
     */
    private $default = false;

    /**
     * @var string $scope
     *
     * @MongoDB\String
     */
    private $scope = self::SCOPE_PERSONAL;

    /**
     * @var integer $rank
     *
     * @MongoDB\Int
     * @Gedmo\SortablePosition
     */
    private $rank;

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set permissions
     *
     * @param array $permissions
     */
    public function setPermissions(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Get permissions
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Add permission
     *
     * @param string $permission
     */
    public function addPermission($permission)
    {
        $this->permissions[] = $permission;

        return $this->permissions = array_unique($this->permissions);
    }

    /**
     * Remove permission
     *
     * @param  string  $permission
     * @return boolean TRUE if this PermissionProfile contains the specified permission, FALSE otherwise
     */
    public function removePermission($permission)
    {
        $permission = array_search($permission, $this->permissions, true);

        if ($permission !== false) {
            unset($this->permissions[$permission]);

            return true;
        }

        return false;
    }

    /**
     * Contains permission
     *
     * @param  string  $permission
     * @return boolean TRUE if this PermissionProfile contains the specified permission, FALSE otherwise
     */
    public function containsPermission($permission)
    {
        return in_array($permission, $this->permissions, true);
    }

    /**
     * Contains all permissions
     *
     * @param  array   $permissions
     * @return boolean TRUE if this PermissionProfile contains all permissions, FALSE otherwise
     */
    public function containsAllPermissions(array $permissions)
    {
        return count(array_intersect($permissions, $this->permissions)) === count($permissions);
    }

    /**
     * Contains any permissions
     *
     * @param  array   $permissions
     * @return boolean TRUE if this PermissionProfile contains any permission of the list, FALSE otherwise
     */
    public function containsAnyPermission(array $permissions)
    {
        return (count(array_intersect($permissions, $this->permissions)) != 0);
    }

    /**
     * Set system
     *
     * @param boolean $system
     */
    public function setSystem($system)
    {
        $this->system = $system;
    }

    /**
     * Get system
     *
     * @return boolean
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * Set default
     *
     * @param boolean $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * Get default
     *
     * @return boolean
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set scope
     *
     * @param integer $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * Get scope
     *
     * @return integer
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    /**
     * Get rank
     *
     * @return integer
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Helper function to know if the
     * PermissionProfile is a global scope
     *
     * @return boolean
     */
    public function isGlobal()
    {
        return self::SCOPE_GLOBAL === $this->getScope();
    }

    /**
     * Helper function to know if the
     * PermissionProfile is a personal scope
     *
     * @return boolean
     */
    public function isPersonal()
    {
        return self::SCOPE_PERSONAL === $this->getScope();
    }

    /**
     * Helper function to know if the
     * PermissionProfile is a none scope
     *
     * @return boolean
     */
    public function isNone()
    {
        return self::SCOPE_NONE === $this->getScope();
    }

    /**
     * To String
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Is system
     *
     * @return boolean
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * Is default
     *
     * @return boolean
     */
    public function isDefault()
    {
        return $this->default;
    }
}
