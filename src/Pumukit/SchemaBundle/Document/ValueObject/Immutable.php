<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

use Doctrine\ODM\MongoDB\Mapping\Attribute as MongoDB;
use Symfony\Component\Security\Core\User\UserInterface;

#[MongoDB\EmbeddedDocument]
class Immutable
{
    #[MongoDB\Field(type: 'bool')]
    protected $value = false;

    #[MongoDB\Field(type: 'date')]
    protected $date;

    #[MongoDB\Field(type: 'object_id')]
    protected $user;

    private function __construct(bool $value, ?UserInterface $user)
    {
        $this->value = $value;
        $this->date = new \DateTime();
        $userId = $user?->getId();
        $this->user = $userId ? (string) $userId : null;
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

    public function user(): ?string
    {
        return null === $this->user ? null : (string) $this->user;
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
