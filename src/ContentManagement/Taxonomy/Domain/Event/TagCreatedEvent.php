<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Domain\Event;

use App\Shared\Domain\DomainEvent;

final class TagCreatedEvent extends DomainEvent
{
    public function __construct(
        public string $tagId,
        public string $cod,
        public array $title,
        public ?string $parentId
    ) {
        parent::__construct();
    }

    public static function fromTag(string $tagId, string $cod, array $title, ?string $parentId): self
    {
        return new self($tagId, $cod, $title, $parentId);
    }

    public function eventName(): string
    {
        return 'tag.created';
    }
}
