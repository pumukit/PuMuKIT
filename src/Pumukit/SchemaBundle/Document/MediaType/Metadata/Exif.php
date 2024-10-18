<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType\Metadata;

final class Exif implements MediaMetadata
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

    public static function create(?string $metadata): Exif
    {
        return new self($metadata);
    }

    public function isEmpty(): bool
    {
        return empty($this->metadata);
    }

    public function width(): ?int
    {
        $metadata = $this->decodeMetadataInfo();

        return $metadata[0]->ImageWidth;
    }

    public function height(): ?int
    {
        $metadata = $this->decodeMetadataInfo();

        return $metadata[0]->ImageHeight;
    }

    private function decodeMetadataInfo()
    {
        return json_decode($this->metadata(), false, 512, JSON_THROW_ON_ERROR);
    }
}
