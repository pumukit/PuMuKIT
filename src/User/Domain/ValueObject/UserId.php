<?php

namespace App\User\Domain\ValueObject;

use MongoDB\BSON\ObjectId;

final class UserId
{
    private ObjectId $id;

    private function __construct(ObjectId $id)
    {
        $this->id = $id;
    }

    public static function fromObjectId(ObjectId $id): self
    {
        return new self($id);
    }

    public static function fromString(string $id): self
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            throw new \InvalidArgumentException("UserId inválido: {$id}");
        }

        return new self(new ObjectId($id));
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
        return $this->id === $other->id;
    }
}
