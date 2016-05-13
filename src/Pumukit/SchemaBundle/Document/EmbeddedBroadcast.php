<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pumukit\SchemaBundle\Document\EmbeddedBroadcast
 *
 * @MongoDB\EmbeddedDocument
 */
class EmbeddedBroadcast
{
    const TYPE_PUBLIC = 'public';
    const TYPE_PASSWORD = 'password';
    const TYPE_LDAP = 'ldap';
    const TYPE_GROUPS = 'groups';

    const NAME_PUBLIC = 'Public';
    const NAME_PASSWORD = 'Protected with Password';
    const NAME_LDAP = 'Protected with LDAP';
    const NAME_GROUPS = 'Protected with Groups';

    /**
     * @var int $id
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string $name
     *
     * @MongoDB\String
     * @MongoDB\UniqueIndex(safe=1)
     */
    private $name = self::NAME_PUBLIC;

    /**
     * @var string $type
     *
     * @MongoDB\String
     */
    private $type = self::TYPE_PUBLIC;

    /**
     * @var string $password
     *
     * @MongoDB\String
     */
    private $password;

    /**
     * @var ArrayCollection $groups
     *
     * @MongoDB\ReferenceMany(targetDocument="Group", simple=true)
     */
    private $groups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
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
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Contains group
     *
     * @param Group $group
     *
     * @return boolean
     */
    public function containsGroup(Group $group)
    {
        return $this->groups->contains($group);
    }

    /**
     * Add admin group
     *
     * @param Group $group
     */
    public function addGroup(Group $group)
    {
        return $this->groups->add($group);
    }

    /**
     * Remove admin group
     *
     * @param Group $group
     */
    public function removeGroup(Group $group)
    {
        $this->groups->removeElement($group);
    }

    /**
     * Get groups
     *
     * @return ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * to String
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @Assert\True(message = "Password required if not public")
     */
    public function isPasswordValid()
    {
        return ((self::TYPE_PUBLIC == $this->getType()) || 
                ((self::TYPE_PASSWORD == $this->getType()) && ("" != $this->getPassword())));
    }
}
