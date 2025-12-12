<?php

declare(strict_types=1);

namespace App\Shared\Domain;

abstract readonly class DomainEvent
{
    public function __construct(
        public string $eventId = '',
        public \DateTimeImmutable $occurredOn = new \DateTimeImmutable()
    ) {}

    abstract public function eventName(): string;

    public function eventId(): string
    {
        return $this->eventId ?: uniqid('', true);
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
