<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\AnnotationRepository")
 */
class Annotation
{
    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="object_id")
     */
    private $multimediaObject;

    /**
     * @MongoDB\Field(type="date")
     */
    private $created;

    /**
     * @MongoDB\Field(type="string")
     */
    private $type;

    /**
     * @MongoDB\Field(type="string")
     */
    private $user_id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $session;

    /**
     * @MongoDB\Field(type="int")
     */
    private $inpoint;

    /**
     * @MongoDB\Field(type="int")
     */
    private $outpoint;

    /**
     * @MongoDB\Field(type="int")
     */
    private $length;

    /**
     * @MongoDB\Field(type="string")
     */
    private $value;

    /**
     * @MongoDB\Field(type="boolean")
     */
    private $is_private;

    public function __clone()
    {
        $this->id = null;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setMultimediaObject($multimediaObject): self
    {
        $this->multimediaObject = $multimediaObject;

        return $this;
    }

    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setUserId(string $userId): self
    {
        $this->user_id = $userId;

        return $this;
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function setSession(string $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getSession(): string
    {
        return $this->session;
    }

    public function setInpoint(int $inpoint): self
    {
        $this->inpoint = $inpoint;

        return $this;
    }

    public function getInpoint(): int
    {
        return $this->inpoint;
    }

    public function setOutpoint(int $outpoint): self
    {
        $this->outpoint = $outpoint;

        return $this;
    }

    public function getOutpoint(): int
    {
        return $this->outpoint;
    }

    public function setLength(int $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setPrivate(bool $isPrivate): self
    {
        $this->is_private = $isPrivate;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->is_private;
    }
}
