<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

 use Pumukit\SchemaBundle\Document\MediaType\Metadata\MediaMetadata;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;

final class Document implements Media
{
    private const TYPE = 3;
    private $id;
    private $originalName;
    private $type;
    private $description;
    private $hide;
    private $tags;
    private $download;
    private $views;
    private $storage;
    private $metadata;

    private function __construct(
        string        $originalName,
        i18nText      $description,
        Tags          $tags,
        bool          $hide,
        bool          $isDownloadable,
        int           $views,
        Storage       $storage,
        MediaMetadata $mediaMetadata
    ) {
        $this->id = new ObjectId();
        $this->originalName = $originalName;
        $this->type = self::TYPE;
        $this->description = $description;
        $this->tags = $tags;
        $this->hide = $hide;
        $this->download = $isDownloadable;
        $this->views = $views;
        $this->storage = $storage;
        $this->metadata = $mediaMetadata;
    }

    public static function create(
        string        $originalName,
        i18nText      $description,
        Tags          $tags,
        bool          $hide,
        bool          $isDownloadable,
        int           $views,
        Storage       $storage,
        MediaMetadata $mediaMetadata
    ): Media
    {
        return new self($originalName, $description, $tags, $hide, $isDownloadable, $views, $storage, $mediaMetadata);
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function id(): ObjectId
    {
        return $this->id;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function type(): int
    {
        return $this->type;
    }

    public function description(): i18nText
    {
        return $this->description;
    }

    public function tags(): Tags
    {
        return $this->tags;
    }

    public function isHide(): bool
    {
        return $this->hide;
    }

    public function isDownloadable(): bool
    {
        return $this->download;
    }


    public function views(): int
    {
        return $this->views;
    }

    public function storage(): Storage
    {
        return $this->storage;
    }

    public function metadata(): MediaMetadata
    {
        return $this->metadata;
    }

}
