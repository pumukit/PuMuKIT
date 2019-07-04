<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use FOS\UserBundle\Model\GroupInterface;

/**
 * Pumukit\SchemaBundle\Document\Group.
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\GroupRepository")
 */
class Group implements GroupInterface
{
    const ORIGIN_LOCAL = 'local';

    /**
     * @var string
     *
     * @MongoDB\Id(strategy="auto")
     */
    protected $id;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * @MongoDB\UniqueIndex(order="asc")
     * @Assert\Regex("/^\w*$/")
     */
    protected $key;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * @MongoDB\UniqueIndex(order="asc")
     */
    protected $name;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $comments;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $origin = self::ORIGIN_LOCAL;

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(type="date")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(type="date")
     */
    private $updatedAt;

    /**
     * Constructor.
     *
     * @param string $key
     */
    public function __construct($key = null)
    {
        $this->key = $key;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

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
     * Set key.
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set comments.
     *
     * @param string $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * Get comments.
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
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
     * Get createdAt.
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @param string $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get updatedAt.
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Defined to implement GroupInterface.
     *
     * @param string $role
     *
     * @return self
     */
    public function addRole($role)
    {
        return $this;
    }

    /**
     * Defined to implement GroupInterface.
     *
     * @param string $role
     *
     * @return bool
     */
    public function hasRole($role)
    {
        return false;
    }

    /**
     * Defined to implement GroupInterface.
     *
     * Note: If implementation changes User::getRoles must be updated
     *
     * @return array
     */
    public function getRoles()
    {
        return [];
    }

    /**
     * Defined to implement GroupInterface.
     *
     * @param string $role
     *
     * @return self
     */
    public function removeRole($role)
    {
        return $this;
    }

    /**
     * Defined to implement GroupInterface.
     *
     * @param array $roles
     *
     * @return self
     */
    public function setRoles(array $roles)
    {
        return $this;
    }

    /**
     * To string.
     *
     * @return string
     */
    public function __toString()
    {
        return null === $this->key ? '' : $this->key;
    }
}
