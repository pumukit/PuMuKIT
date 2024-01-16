<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType\Metadata;

final class VideoAudio implements MediaMetadata
{
    private string $metadata;

    private function __construct(?string $metadata)
    {
        $this->metadata = $metadata;
    }

    public function metadata(): ?string
    {
        return $this->metadata;
    }

    public static function create(?string $metadata): VideoAudio
    {
        return new self($metadata);
    }

    public function duration(): int
    {
        return (int) ceil((float) $this->metadata->format->duration) ?? 0;
    }
}
