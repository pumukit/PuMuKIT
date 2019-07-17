<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use FOS\UserBundle\Model\GroupInterface;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * Pumukit\SchemaBundle\Document\User.
 *
 * @MongoDB\Document
 */
class User extends BaseUser
{
    use Traits\Properties;

    const ORIGIN_LOCAL = 'local';

    /**
     * @var int
     *
     * @MongoDB\Id(strategy="auto")
     */
    protected $id;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $fullname;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $origin = self::ORIGIN_LOCAL;

    /**
     * @var ArrayCollection
     *
     * @MongoDB\ReferenceMany(targetDocument="Group", storeAs="id", sort={"key":1}, strategy="setArray")
     */
    protected $groups;

    /**
     * @MongoDB\ReferenceOne(targetDocument="PermissionProfile", storeAs="id", cascade={"persist"})
     */
    private $permissionProfile;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Person", inversedBy="user", storeAs="id", cascade={"persist"})
     */
    private $person;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
        parent::__construct();
    }

    /**
     * Set permission profile.
     *
     * @param PermissionProfile $permissionProfile
     */
    public function setPermissionProfile(PermissionProfile $permissionProfile)
    {
        $this->permissionProfile = $permissionProfile;
    }

    /**
     * Get permission profile.
     *
     * @return PermissionProfile $permissionProfile
     */
    public function getPermissionProfile()
    {
        return $this->permissionProfile;
    }

    /**
     * Set person.
     *
     * @param Person $person
     */
    public function setPerson(Person $person = null)
    {
        $this->person = $person;
    }

    /**
     * Get person.
     *
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set fullname.
     *
     * @param string $fullname
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;
    }

    /**
     * Get fullname.
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set origin.
     *
     * @param string $origin
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }

    /**
     * Get origin.
     *
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Is the origin local.
     *
     * @return bool
     */
    public function isLocal()
    {
        return self::ORIGIN_LOCAL == $this->origin;
    }

    /**
     * Contains Group.
     *
     * @param GroupInterface $group
     *
     * @return bool
     */
    public function containsGroup(GroupInterface $group)
    {
        return $this->groups->contains($group);
    }

    /**
     * Add  group.
     *
     * @param GroupInterface $group
     */
    public function addGroup(GroupInterface $group)
    {
        return $this->groups->add($group);
    }

    /**
     * Remove  group.
     *
     * @param GroupInterface $group
     */
    public function removeGroup(GroupInterface $group)
    {
        $this->groups->removeElement($group);
    }

    /**
     * Get Groups.
     *
     * @return ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Get groups ids.
     *
     * @return array
     */
    public function getGroupsIds()
    {
        // Performance boost (Don't repeat it, only if it's exceptionally necesary)
        if ($this->groups instanceof \Doctrine\ODM\MongoDB\PersistentCollection && !$this->groups->isDirty()) {
            //See PersistentCollection class (coll + mongoData)
            return array_merge(
                array_map(function ($g) {
                    return new \MongoId($g->getId());
                }, $this->groups->unwrap()->toArray()),
                $this->groups->getMongoData()
            );
        }

        return array_map(function ($g) {
            return new \MongoId($g->getId());
        }, $this->groups->toArray());
    }

    /**
     * Returns the user roles.
     *
     * Note: Override BaseUser::getRole to avoid call User::getGroups and avoid a query.
     *
     * @return array The roles
     */
    public function getRoles()
    {
        $roles = $this->roles;
        $roles[] = static::ROLE_DEFAULT;

        return array_unique($roles);
    }
}
