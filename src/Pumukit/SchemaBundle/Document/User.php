<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use FOS\UserBundle\Document\User as BaseUser;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pumukit\SchemaBundle\Document\User
 *
 * @MongoDB\Document
 */
class User extends BaseUser
{
    /**
     * @var int $id
     *
     * @MongoDB\Id(strategy="auto")
     */
    protected $id;

    /**
     * @MongoDB\ReferenceOne(targetDocument="PermissionProfile", simple=true)
     */
    private $permissionProfile;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Person", inversedBy="user", simple=true)
     */
    private $person;

    /**
     * @var string $fullname
     *
     * @MongoDB\String
     */
    protected $fullname;

    /**
     * @var string $origin
     *
     * @MongoDB\String
     */
    protected $origin = 'local';

    /**
     * @var ArrayCollection $adminGroups
     *
     * @MongoDB\ReferenceMany(targetDocument="Group", simple=true)
     */
    private $adminGroups;

    /**
     * @var ArrayCollection $memberGroups
     *
     * @MongoDB\ReferenceMany(targetDocument="Group", simple=true)
     */
    private $memberGroups;

    /**
     * Constructor
     */
    public function __construct($genUserSalt = false)
    {
        $this->adminGroups = new ArrayCollection();
        $this->memberGroups = new ArrayCollection();
        parent::__construct();
        if(false == $genUserSalt){
            $this->salt = '';
        }
    }

    /**
     * Set permission profile
     *
     * @param PermissionProfile $permissionProfile
     */
    public function setPermissionProfile(PermissionProfile $permissionProfile)
    {
        $this->permissionProfile = $permissionProfile;
    }

    /**
     * Get permission profile
     *
     * @return PermissionProfile $permissionProfile
     */
    public function getPermissionProfile()
    {
        return $this->permissionProfile;
    }

    /**
     * Set person
     *
     * @param Person $person
     */
    public function setPerson(Person $person = null)
    {
        $this->person = $person;
    }

    /**
     * Get person
     *
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set fullname
     *
     * @param string $fullname
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;
    }

    /**
     * Get fullname
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set origin
     *
     * @param string $origin
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }

    /**
     * Get origin
     *
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Contains adminGroup
     *
     * @param Group $adminGroup
     *
     * @return boolean
     */
    public function containsAdminGroup(Group $adminGroup)
    {
        return $this->adminGroups->contains($adminGroup);
    }

    /**
     * Add admin group
     *
     * @param Group $adminGroup
     */
    public function addAdminGroup(Group $adminGroup)
    {
        return $this->adminGroups->add($adminGroup);
    }

    /**
     * Remove admin group
     *
     * @param Group $adminGroup
     */
    public function removeAdminGroup(Group $adminGroup)
    {
        $this->adminGroups->removeElement($adminGroup);
    }

    /**
     * Get adminGroups
     *
     * @return ArrayCollection
     */
    public function getAdminGroups()
    {
        return $this->adminGroups;
    }

    /**
     * Contains memberGroup
     *
     * @param Group $memberGroup
     *
     * @return boolean
     */
    public function containsMemberGroup(Group $memberGroup)
    {
        return $this->memberGroups->contains($memberGroup);
    }

    /**
     * Add member group
     *
     * @param Group $memberGroup
     */
    public function addMemberGroup(Group $memberGroup)
    {
        return $this->memberGroups->add($memberGroup);
    }

    /**
     * Remove member group
     *
     * @param Group $memberGroup
     */
    public function removeMemberGroup(Group $memberGroup)
    {
        $this->memberGroups->removeElement($memberGroup);
    }

    /**
     * Get memberGroups
     *
     * @return ArrayCollection
     */
    public function getMemberGroups()
    {
        return $this->memberGroups;
    }
}
