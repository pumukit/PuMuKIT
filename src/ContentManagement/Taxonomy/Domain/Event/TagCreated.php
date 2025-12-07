<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Domain\Event;

final readonly class TagCreated
{
    public function __construct(
        public string $tagId,
        public string $cod,
        public array $title,
        public ?string $parentId,
        public \DateTimeImmutable $occurredOn
    ) {}

    public static function fromTag(string $tagId, string $cod, array $title, ?string $parentId): self
    {
        return new self(
            $tagId,
            $cod,
            $title,
            $parentId,
            new \DateTimeImmutable()
        );
    }
}
