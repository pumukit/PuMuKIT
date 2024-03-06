<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType\Metadata;

final class Generic implements MediaMetadata
{
    private string $metadata;

    private function __construct(?string $metadata)
    {
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        return [
            'metadata' => $this->metadata,
        ];
    }

    public function toString(): string
    {
        return $this->metadata() ?? '';
    }

    public function metadata(): ?string
    {
        return $this->metadata;
    }

    public static function create(?string $metadata): Generic
    {
        return new self($metadata);
    }

    public function isEmpty(): bool
    {
        return empty($this->metadata);
    }
}
