<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\Exif;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\MediaMetadata;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\Traits\Properties;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;

/**
 * @MongoDB\MappedSuperclass
 */
abstract class Media implements MediaInterface
{
    use Properties;

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
     * @MongoDB\Field(type="string")
     */
    private $language;

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
        string $language,
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
        $this->language = $language;
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

    public function getId(): string
    {
        return $this->id();
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function getOriginalName(): string
    {
        return $this->originalName();
    }

    public function description(): i18nText
    {
        return i18nText::create($this->description);
    }

    public function getDescription(): i18nText
    {
        return $this->description();
    }

    public function language(): ?string
    {
        return $this->language;
    }

    public function getLanguage(): ?string
    {
        return $this->language();
    }

    public function tags(): Tags
    {
        return Tags::create($this->tags);
    }

    public function getTags(): array
    {
        return $this->tags()->toArray();
    }

    public function isHide(): bool
    {
        return $this->hide;
    }

    public function isVisible(): bool
    {
        return !$this->isHide();
    }

    public function isDownloadable(): bool
    {
        return $this->download;
    }

    public function views(): int
    {
        return $this->views;
    }

    public function getViews(): int
    {
        return $this->views();
    }

    public function storage(): Storage
    {
        if (null === $this->storage['path']) {
            return Storage::external(StorageUrl::create($this->storage['url']));
        }

        return Storage::create(
            StorageUrl::create($this->storage['url']) ?? null,
            Path::create($this->storage['path']) ?? null,
        );
    }

    public function getUrl(): ?string
    {
        if (null === $this->storage['path']) {
            return $this->storage['url'];
        }

        return $this->storage['path'];
    }

    public function metadata(): MediaMetadata
    {
        return VideoAudio::create($this->metadata);
    }

    public function getDuration(): int
    {
        return $this->metadata()->duration() ?? 0;
    }

    public function videoMetadata(): MediaMetadata
    {
        return VideoAudio::create($this->metadata);
    }

    public function imageMetadata(): MediaMetadata
    {
        return Exif::create($this->metadata);
    }

    public function documentMetadata(): MediaMetadata
    {
        return Exif::create($this->metadata);
    }

    public function isMaster(): bool
    {
        return $this->tags()->contains('master');
    }

    public function profileName(): ?string
    {
        foreach ($this->tags()->toArray() as $tag) {
            if (str_starts_with($tag, 'profile:')) {
                return substr($tag, 8);
            }
        }

        return null;
    }

    public function updateId(string $id): void
    {
        $this->id = new ObjectId($id);
    }

    public function updateTags(Tags $tags): void
    {
        $this->tags = $tags->toArray();
    }

    public function updateStorage(Storage $storage): void
    {
        $this->storage = $storage->toArray();
    }

    public function updateDescription(i18nText $description): void
    {
        $this->description = $description->toArray();
    }

    public function updateLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function changeHide(): void
    {
        $this->hide = !$this->hide;
    }

    public function updateHide(bool $hide): void
    {
        $this->hide = $hide;
    }

    public function updateDownload(bool $download): void
    {
        $this->download = $download;
    }

    public function mimeType(): string
    {
        return mime_content_type($this->storage()->path()->path());
    }

    public function updateMetadata(MediaMetadata $metadata): void
    {
        $this->metadata = $metadata->toString();
    }

    abstract protected static function create(
        string $originalName,
        i18nText $description,
        string $language,
        Tags $tags,
        bool $hide,
        bool $isDownloadable,
        int $views,
        Storage $storage,
        MediaMetadata $mediaMetadata
    ): MediaInterface;
}
