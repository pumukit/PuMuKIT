<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class TagUpdatedEvent extends DomainEvent
{
    public function __construct(
        public string $tagId,
        public string $cod,
        public array $title
    ) {
        parent::__construct();
    }

    public static function fromTag(string $tagId, string $cod, array $title): self
    {
        return new self($tagId, $cod, $title);
    }

    public function eventName(): string
    {
        return 'tag.updated';
    }
}
