<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pumukit\SchemaBundle\Document\Group
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\GroupRepository")
 */
class Group
{
    const ORIGIN_LOCAL = 'local';
    const ORIGIN_LDAP = 'ldap';

    /**
     * @var string $id
     *
     * @MongoDB\Id(strategy="auto")
     */
    protected $id;

    /**
     * @var string $key
     *
     * @MongoDB\String
     * @MongoDB\UniqueIndex(order="asc")
     * @Assert\Regex("/^\w*$/")
     */
    protected $key;

    /**
     * @var string $name
     *
     * @MongoDB\String
     * @MongoDB\UniqueIndex(order="asc")
     */
    protected $name;

    /**
     * @var string $origin
     *
     * @MongoDB\String
     */
    protected $origin = self::ORIGIN_LOCAL;

    /**
     * @var date $createdAt
     *
     * @MongoDB\Date
     */
    private $createdAt;

    /**
     * @var date $updatedAt
     *
     * @MongoDB\Date
     */
    private $updatedAt;

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     * @var locale $locale
     */
    private $locale = 'en';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \Datetime('now');
        $this->updatedAt = new \Datetime('now');
    }

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
     * Set key
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
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
     * Get createdAt
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param string $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get updatedAt
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
