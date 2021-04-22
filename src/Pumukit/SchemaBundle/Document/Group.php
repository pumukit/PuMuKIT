<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\GroupRepository")
 */
class Group implements GroupInterface
{
    public const ORIGIN_LOCAL = 'local';

    /**
     * @MongoDB\Id(strategy="auto")
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     * @MongoDB\UniqueIndex(order="asc")
     * @Assert\Regex("/^\w*$/")
     */
    protected $key;

    /**
     * @MongoDB\Field(type="string")
     * @MongoDB\UniqueIndex(order="asc")
     */
    protected $name;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $comments;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $origin = self::ORIGIN_LOCAL;

    /**
     * @MongoDB\Field(type="date")
     */
    private $createdAt;

    /**
     * @MongoDB\Field(type="date")
     */
    private $updatedAt;

    public function __construct($key = null)
    {
        $this->key = $key;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->key ?? '';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name): Group
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setComments($comments): void
    {
        $this->comments = $comments;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setOrigin($origin): void
    {
        $this->origin = $origin;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function isLocal(): bool
    {
        return self::ORIGIN_LOCAL === $this->origin;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setUpdatedAt($updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function addRole($role): Group
    {
        return $this;
    }

    public function hasRole($role): bool
    {
        return false;
    }

    public function getRoles(): array
    {
        return [];
    }

    public function removeRole($role): Group
    {
        return $this;
    }

    public function setRoles(array $roles): Group
    {
        return $this;
    }
}
