<?php

declare(strict_types=1);

namespace App\Taxonomy\Domain\Event;

final readonly class TagDeleted
{
    public function __construct(
        public string $tagId,
        public string $cod,
        public \DateTimeImmutable $occurredOn
    ) {}

    public static function fromTag(string $tagId, string $cod): self
    {
        return new self(
            $tagId,
            $cod,
            new \DateTimeImmutable()
        );
    }
}
