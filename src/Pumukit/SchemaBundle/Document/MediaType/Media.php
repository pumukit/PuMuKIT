<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\MediaMetadata;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
Use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;

/**
 * @MongoDB\MappedSuperclass
 */
abstract class Media implements MediaInterface
{
    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $originalName;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $description;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $hide;

    /**
     * @MongoDB\Field(type="collection")
     */
    private $tags;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $download;

    /**
     * @MongoDB\Field(type="int", strategy="increment")
     */
    private $views;

    /**
     * @MongoDB\Field(type="collection")
     */
    private $storage;

    /**
     * @MongoDB\Field(type="collection")
     */
    private $metadata;

    protected function __construct(
        string $originalName,
        i18nText $description,
        Tags $tags,
        bool $hide,
        bool $isDownloadable,
        int $views,
        Storage $storage,
        MediaMetadata $mediaMetadata
    ) {
        $this->id = new ObjectId();
        $this->originalName = $originalName;
        $this->description = $description;
        $this->tags = $tags;
        $this->hide = $hide;
        $this->download = $isDownloadable;
        $this->views = $views;
        $this->storage = $storage;
        $this->metadata = $mediaMetadata;
    }

    abstract protected static function create(
        string $originalName,
        i18nText $description,
        Tags $tags,
        bool $hide,
        bool $isDownloadable,
        int $views,
        Storage $storage,
        MediaMetadata $mediaMetadata
    ): MediaInterface;

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