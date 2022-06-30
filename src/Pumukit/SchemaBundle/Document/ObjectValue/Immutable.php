<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ObjectValue;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @MongoDB\EmbeddedDocument()
 */
class Immutable
{
    /**
     * @MongoDB\Field(type="boolean")
     */
    protected $value = false;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $date;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $user;

    private function __construct(bool $value, ?UserInterface $user)
    {
        $this->value = $value;
        $this->date = new \DateTime();
        if ($user) {
            $this->user = new ObjectId($user->getId());
        } else {
            $this->user = null;
        }
    }

    public static function create(bool $value, ?UserInterface $user): self
    {
        return new self($value, $user);
    }

    public function value(): bool
    {
        return $this->value;
    }

    public function date(): \DateTimeInterface
    {
        return $this->date;
    }

    public function user(): ?ObjectId
    {
        return $this->user ?? null;
    }

    public function isBlocked(): bool
    {
        return self::value();
    }

    public function hasUser(): bool
    {
        return (bool) $this->user;
    }
}
