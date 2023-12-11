<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

use Pumukit\SchemaBundle\Document\Element;

final class Image extends Element implements Media
{
    private $originalName;
    private $download;
    private $views;
    private $metadata;

    private function __construct(string $originalName, bool $isDownloadable, int $views, MediaMetadata $mediaMetadata)
    {
        $this->originalName = $originalName;
        $this->download = $isDownloadable;
        $this->views = $views;
        $this->metadata = $mediaMetadata;
        parent::__construct();
    }

    public static function create(string $originalName, bool $isDownloadable, int $views, MediaMetadata $mediaMetadata): Media
    {
        return new self($originalName, $isDownloadable, $views, $mediaMetadata);
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
