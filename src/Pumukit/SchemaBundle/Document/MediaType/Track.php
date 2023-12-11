<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

use Pumukit\SchemaBundle\Document\Element;

final class Track implements Media
{
    private $originalName;
    private $download;
    private $views;

    private $storage;
    private $metadata;

    private function __construct(string $originalName, bool $isDownloadable, int $views, Storage $storage, MediaMetadata $mediaMetadata)
    {
        $this->originalName = $originalName;
        $this->download = $isDownloadable;
        $this->views = $views;
        $this->storage = $storage;
        $this->metadata = $mediaMetadata;
    }

    public static function create(string $originalName, bool $isDownloadable, int $views, Storage $storage, MediaMetadata $mediaMetadata): Media
    {
        return new self($originalName, $isDownloadable, $views, $storage, $mediaMetadata);
    }

    public function __toString(): string
    {
        // TODO: Implement __toString() method.
    }

    public function originalName(): void
    {
        // TODO: Implement originalName() method.
    }

    public function isDownloadable(): bool
    {
        // TODO: Implement download() method.
    }

    public function views(): int
    {
        // TODO: Implement views() method.
    }

    public function metadata(): MediaMetadata
    {
        // TODO: Implement fileInfo() method.
    }


}
