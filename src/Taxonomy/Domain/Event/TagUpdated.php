<?php

declare(strict_types=1);

namespace App\Taxonomy\Domain\Event;

use DateTimeImmutable;

final readonly class TagUpdated
{
    public function __construct(
        public string $tagId,
        public string $cod,
        public array $title,
        public DateTimeImmutable $occurredOn
    ) {}

    public static function fromTag(string $tagId, string $cod, array $title): self
    {
        return new self(
            $tagId,
            $cod,
            $title,
            new DateTimeImmutable()
        );
    }
}

