<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class PersonUpdatedEvent extends DomainEvent
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email
    ) {
        parent::__construct();
    }

    public static function fromPerson(string $id, string $name, ?string $email): self
    {
        return new self($id, $name, $email);
    }

    public function eventName(): string
    {
        return 'person.updated';
    }
}
