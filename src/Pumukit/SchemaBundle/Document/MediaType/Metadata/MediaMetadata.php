<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType\Metadata;

interface MediaMetadata
{
    public function toArray(): array;

    public function toString(): string;

    public function metadata(): ?string;

    public function isEmpty(): bool;
}
