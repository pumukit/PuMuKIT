<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\UserClearanceRepository")
 */
class UserClearance
{
    const SCOPE_GLOBAL = 'SCOPE_GLOBAL';
    const SCOPE_PERSONAL = 'SCOPE_PERSONAL';
    const SCOPE_NONE = 'SCOPE_NONE';

    public static $scopeDescription = array(
                                            UserClearance::SCOPE_GLOBAL => 'Global Scope',
                                            UserClearance::SCOPE_PERSONAL => 'Personal Scope',
                                            UserClearance::SCOPE_NONE => 'No Scope'
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
    private $name = "";

    /**
     * @var array $clearances
     *
     * @MongoDB\Collection
     */
    private $clearances = array();

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
    private $scope = self::SCOPE_NONE;

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
     * Set clearances
     *
     * @param array $clearances
     */
    public function setClearances(array $clearances)
    {
        $this->clearances = $clearances;
    }

    /**
     * Get clearances
     *
     * @return array
     */
    public function getClearances()
    {
        return $this->clearances;
    }

    /**
     * Add clearance
     *
     * @param string $clearance
     */
    public function addClearance($clearance)
    {
        $this->clearances[] = $clearance;

        return $this->clearances = array_unique($this->clearances);
    }

    /**
     * Remove clearance
     *
     * @param  string  $clearance
     * @return boolean TRUE if this UserClearance contains the specified clearance, FALSE otherwise
     */
    public function removeClearance($clearance)
    {
        $clearance = array_search($clearance, $this->clearances, true);

        if ($clearance !== false) {
            unset($this->clearances[$clearance]);

            return true;
        }

        return false;
    }

    /**
     * Contains clearance
     *
     * @param  string  $clearance
     * @return boolean TRUE if this UserClearance contains the specified clearance, FALSE otherwise
     */
    public function containsClearance($clearance)
    {
        return in_array($clearance, $this->clearances, true);
    }

    /**
     * Contains all clearances
     *
     * @param  array   $clearances
     * @return boolean TRUE if this UserClearance contains all clearances, FALSE otherwise
     */
    public function containsAllClearances(array $clearances)
    {
        return count(array_intersect($clearances, $this->clearances)) === count($clearances);
    }

    /**
     * Contains any clearances
     *
     * @param  array   $clearances
     * @return boolean TRUE if this UserClearance contains any clearance of the list, FALSE otherwise
     */
    public function containsAnyClearance(array $clearances)
    {
        return (count(array_intersect($clearances, $this->clearances)) != 0);
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
     * Helper function to know if the
     * UserClearance is a global scope
     *
     * @return boolean
     */
    public function isGlobal()
    {
        return self::SCOPE_GLOBAL === $this->getScope();
    }

    /**
     * Helper function to know if the
     * UserClearance is a personal scope
     *
     * @return boolean
     */
    public function isPersonal()
    {
        return self::SCOPE_PERSONAL === $this->getScope();
    }

    /**
     * Helper function to know if the
     * UserClearance is a none scope
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