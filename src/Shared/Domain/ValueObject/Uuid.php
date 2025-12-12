<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Validator\UuidValidator;
use MongoDB\BSON\ObjectId;

class Uuid implements \Stringable
{
    protected function __construct(protected ObjectId $id)
    {
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @phpstan-return static
     */
    public static function fromObjectId(ObjectId $id): static
    {
        return new self($id);
    }

    /**
     * @phpstan-return static
     */
    public static function fromString(string $id): static
    {
        UuidValidator::validate($id, 'UUID');

        return new self(new ObjectId($id));
    }

    /**
     * @phpstan-return static
     */
    public static function generate(): static
    {
        return new self(new ObjectId());
    }

    public static function isValid(string $id): bool
    {
        return UuidValidator::isValid($id);
    }

    public function toObjectId(): ObjectId
    {
        return $this->id;
    }

    public function toString(): string
    {
        return (string) $this->id;
    }

    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function value(): ObjectId
    {
        return $this->id;
    }
}
