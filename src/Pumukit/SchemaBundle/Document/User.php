<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use FOS\UserBundle\Document\User as BaseUser;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\GroupInterface;

/**
 * Pumukit\SchemaBundle\Document\User
 *
 * @MongoDB\Document
 */
class User extends BaseUser
{
    const ORIGIN_LOCAL = 'local';

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
    protected $origin = self::ORIGIN_LOCAL;

    /**
     * @var ArrayCollection $groups
     *
     * @MongoDB\ReferenceMany(targetDocument="Group", simple=true)
     */
    protected $groups;

    /**
     * Constructor
     */
    public function __construct($genUserSalt = false)
    {
        $this->groups = new ArrayCollection();
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
     * Contains Group
     *
     * @param GroupInterface $group
     *
     * @return boolean
     */
    public function containsGroup(GroupInterface $group)
    {
        return $this->groups->contains($group);
    }

    /**
     * Add  group
     *
     * @param GroupInterface $group
     */
    public function addGroup(GroupInterface $group)
    {
        return $this->groups->add($group);
    }

    /**
     * Remove  group
     *
     * @param GroupInterface $group
     */
    public function removeGroup(GroupInterface $group)
    {
        $this->groups->removeElement($group);
    }

    /**
     * Get Groups
     *
     * @return ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Get groups ids
     *
     * @return array
     */
    public function getGroupsIds()
    {
        $groupsIds = array();
        foreach ($this->getGroups() as $group) {
            $groupsIds[] = new \MongoId($group->getId());
        }
        return $groupsIds;
    }
}
