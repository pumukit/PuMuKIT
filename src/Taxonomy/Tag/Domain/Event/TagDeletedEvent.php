<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Domain\Event;

use App\Shared\Domain\DomainEvent;

final class TagDeletedEvent extends DomainEvent
{
    public function __construct(
        public string $tagId,
        public string $cod
    ) {
        parent::__construct();
    }

    public static function fromTag(string $tagId, string $cod): self
    {
        return new self($tagId, $cod);
    }

    public function eventName(): string
    {
        return 'tag.deleted';
    }
}
