<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class PersonDeleted extends DomainEvent
{
    public function __construct(
        public string $id,
        public string $name
    ) {}

    public static function fromPerson(string $id, string $name): self
    {
        return new self($id, $name);
    }

    public function eventName(): string
    {
        return 'person.deleted';
    }
}
