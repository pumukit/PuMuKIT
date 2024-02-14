<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\MediaMetadata;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Document\ValueObject\Url;


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
     * @MongoDB\Field(type="raw")
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
     * @MongoDB\Field(type="raw")
     */
    private $storage;

    /**
     * @MongoDB\Field(type="string")
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
        $this->description = $description->toArray();
        $this->tags = $tags->toArray();
        $this->hide = $hide;
        $this->download = $isDownloadable;
        $this->views = $views;
        $this->storage = $storage->toArray();
        $this->metadata = $mediaMetadata->toString();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function id(): string
    {
        return (string) $this->id;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function description(): i18nText
    {
        return i18nText::create($this->description);
    }

    public function tags(): Tags
    {
        return Tags::create($this->tags);
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
        return Storage::create(
            Url::create($this->storage['url']) ?? null,
            Path::create($this->storage['path']) ?? null,
        );
    }

    public function metadata(): MediaMetadata
    {
        return VideoAudio::create($this->metadata);
    }

    public function isMaster(): bool
    {
        return $this->tags()->contains('master');
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
}
