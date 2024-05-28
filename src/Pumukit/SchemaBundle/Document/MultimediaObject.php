<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;
use Pumukit\SchemaBundle\Document\MediaType\Document;
use Pumukit\SchemaBundle\Document\MediaType\External;
use Pumukit\SchemaBundle\Document\MediaType\Image;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\ValueObject\Immutable;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\MultimediaObjectRepository")
 *
 * @MongoDB\Index(name="text_index", keys={"textindex.text"="text", "secondarytextindex.text"="text"}, options={"language_override"="indexlanguage", "default_language"="none", "weights"={"textindex.text"=10, "secondarytextindex.text"=1}})
 *
 * @ApiResource(
 *       collectionOperations={"get"={"method"="GET", "access_control"="is_granted('ROLE_ACCESS_API')"}},
 *       itemOperations={"get"={"method"="GET", "access_control"="is_granted('ROLE_ACCESS_API')"}}
 *   )
 */
class MultimediaObject
{
    use Traits\Keywords;
    use Traits\Properties;
    use Traits\HeadAndTail;
    use Traits\Link {
        Traits\Link::__construct as private __LinkConstruct;
    }
    use Traits\Pic {
        Traits\Pic::__construct as private __PicConstruct;
    }
    use Traits\Material {
        Traits\Material::__construct as private __MaterialConstruct;
    }

    public const STATUS_PUBLISHED = 0;
    public const STATUS_BLOCKED = 1;
    public const STATUS_HIDDEN = 2;
    public const STATUS_NEW = -1;
    public const STATUS_PROTOTYPE = -2;

    public const TYPE_UNKNOWN = 0;
    public const TYPE_VIDEO = 1;
    public const TYPE_AUDIO = 2;
    public const TYPE_EXTERNAL = 3;
    public const TYPE_LIVE = 4;
    public const TYPE_IMAGE = 5;
    public const TYPE_DOCUMENT = 6;

    public static $statusTexts = [
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_BLOCKED => 'Blocked',
        self::STATUS_HIDDEN => 'Hidden',
        self::STATUS_NEW => 'New',
        self::STATUS_PROTOTYPE => 'Prototype',
    ];

    public static $typeTexts = [
        self::TYPE_UNKNOWN => '',
        self::TYPE_VIDEO => 'Video',
        self::TYPE_AUDIO => 'Audio',
        self::TYPE_EXTERNAL => 'External',
        self::TYPE_LIVE => 'Live',
        self::TYPE_IMAGE => 'Image',
        self::TYPE_DOCUMENT => 'Document',
    ];

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="int")
     *
     * @MongoDB\UniqueIndex()
     */
    private $numerical_id;

    /**
     * @MongoDB\EmbedOne(name="immutable", targetDocument="Pumukit\SchemaBundle\Document\ValueObject\Immutable")
     */
    private $immutable;

    /**
     * @MongoDB\Field(type="int")
     *
     * @MongoDB\Index
     */
    private $type = self::TYPE_UNKNOWN;

    /**
     * @MongoDB\Field(type="string")
     *
     * @MongoDB\Index
     */
    private $secret;

    /**
     * @MongoDB\ReferenceOne(targetDocument=Series::class, storeAs="id", inversedBy="multimedia_object", cascade={"persist"})
     *
     * @Gedmo\SortableGroup
     *
     * @MongoDB\Index
     */
    private $series;

    /**
     * NOTE: This field is for MongoDB Search Index purposes. Do not use this field and do not create setter and/or getter.
     *
     * @MongoDB\Field(type="raw")
     */
    private $seriesTitle = ['en' => ''];

    /**
     * @MongoDB\EmbedOne(targetDocument=EmbeddedBroadcast::class)
     */
    private $embeddedBroadcast;

    /**
     * @MongoDB\EmbedOne(targetDocument=EmbeddedEvent::class)
     */
    private $embeddedEvent;

    /**
     * @MongoDB\EmbedMany(targetDocument=EmbeddedSegment::class)
     */
    private $embeddedSegments;

    /**
     * @MongoDB\EmbedOne(targetDocument=EmbeddedSocial::class)
     */
    private $embeddedSocial;

    /**
     * @MongoDB\EmbedMany(targetDocument=EmbeddedTag::class)
     */
    private $tags;

    /**
     * @MongoDB\EmbedMany(targetDocument=Track::class)
     */
    private $tracks;

    /**
     * @MongoDB\EmbedMany(targetDocument=Document::class)
     */
    private $documents;

    /**
     * @MongoDB\EmbedMany(targetDocument=External::class)
     */
    private $external;

    /**
     * @MongoDB\EmbedMany(targetDocument=Image::class)
     */
    private $images;

    /**
     * @MongoDB\ReferenceMany(targetDocument=Group::class, storeAs="id", sort={"key":1}, strategy="setArray", cascade={"persist","remove"})
     */
    private $groups;

    /**
     * @MongoDB\Field(type="int")
     *
     * @Gedmo\SortablePosition
     */
    private $rank;

    /**
     * @MongoDB\Field(type="int")
     */
    private $status = self::STATUS_NEW;

    /**
     * @MongoDB\Field(type="date")
     *
     * @MongoDB\Index
     */
    private $record_date;

    /**
     * @MongoDB\Field(type="date")
     *
     * @MongoDB\Index
     */
    private $public_date;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $title = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $subtitle = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @MongoDB\Field(type="string")
     */
    private $comments;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $line2 = ['en' => ''];

    /**
     * @MongoDB\Field(type="string")
     */
    private $copyright;

    /**
     * @MongoDB\Field(type="string")
     */
    private $license;

    /**
     * @MongoDB\Field(type="int")
     */
    private $duration = 0;

    /**
     * @MongoDB\Field(type="int", strategy="increment")
     */
    private $numview = 0;

    /**
     * @MongoDB\EmbedMany(targetDocument=EmbeddedRole::class)
     */
    private $people;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $head = false;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $tail = false;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $textindex = [];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $secondarytextindex = [];

    /**
     * Used locale to override Translation listener`s locale this is not a mapped field of entity metadata, just a simple property.
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->secret = base_convert(sha1(uniqid((string) random_int(0, mt_getrandmax()), true)), 16, 36);
        $this->tracks = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->external = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->people = new ArrayCollection();
        $this->groups = new ArrayCollection();

        $this->__LinkConstruct();
        $this->__PicConstruct();
        $this->__MaterialConstruct();

        $now = new \DateTime('now');
        $this->setPublicDate($now);
        $this->setRecordDate($now);
        $this->setPropertyAsDateTime('created', $now);
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function isCollection(): bool
    {
        return false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNumericalID(): int
    {
        return $this->numerical_id;
    }

    public function setNumericalID(int $numericalID): void
    {
        $this->numerical_id = $numericalID;
    }

    public function getImmutable(): ?Immutable
    {
        return $this->immutable;
    }

    public function setImmutable(Immutable $immutable): void
    {
        $this->immutable = $immutable;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function resetSecret(): string
    {
        $this->secret = base_convert(sha1(uniqid((string) random_int(0, mt_getrandmax()), true)), 16, 36);

        return $this->secret;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setRank(int $rank): void
    {
        $this->rank = $rank;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function isLive(): bool
    {
        return self::TYPE_LIVE === $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setVideoType(): void
    {
        $this->type = self::TYPE_VIDEO;
    }

    public function isVideoAudioType(): bool
    {
        return $this->isVideoType() || $this->isAudioType();
    }

    public function isVideoType(): bool
    {
        return self::TYPE_VIDEO === $this->getType();
    }

    public function isAudioType(): bool
    {
        return self::TYPE_AUDIO === $this->getType();
    }

    public function setAudioType(): void
    {
        $this->type = self::TYPE_AUDIO;
    }

    public function setLiveType(): void
    {
        $this->type = self::TYPE_LIVE;
    }

    public function isLiveType(): bool
    {
        return self::TYPE_LIVE === $this->getType();
    }

    public function setImageType(): void
    {
        $this->type = self::TYPE_IMAGE;
    }

    public function isImageType(): bool
    {
        return self::TYPE_IMAGE === $this->getType();
    }

    public function setDocumentType(): void
    {
        $this->type = self::TYPE_DOCUMENT;
    }

    public function isDocumentType(): bool
    {
        return self::TYPE_DOCUMENT === $this->getType();
    }

    public function setExternalType(): void
    {
        $this->type = self::TYPE_EXTERNAL;
    }

    public function isExternalType(): bool
    {
        return self::TYPE_EXTERNAL === $this->getType();
    }

    public function getStringType($type): string
    {
        return self::$typeTexts[$type];
    }

    public function setStatus($status): void
    {
        $this->status = $status;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getStringStatus($status): string
    {
        return self::$statusTexts[$status];
    }

    public function isPublished(): bool
    {
        return self::STATUS_PUBLISHED === $this->getStatus();
    }

    public function isBlocked(): bool
    {
        return self::STATUS_BLOCKED === $this->getStatus();
    }

    public function isHidden(): bool
    {
        return self::STATUS_HIDDEN === $this->getStatus();
    }

    public function isPrototype(): bool
    {
        return self::STATUS_PROTOTYPE === $this->getStatus();
    }

    public function setRecordDate($recordDate): void
    {
        $this->record_date = $recordDate;
    }

    public function getRecordDate()
    {
        return $this->record_date;
    }

    public function setPublicDate($publicDate): void
    {
        $this->public_date = $publicDate;
    }

    public function getPublicDate()
    {
        return $this->public_date;
    }

    public function setTitle($title, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->title[$locale] = $title;
    }

    public function getTitle($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->title[$locale] ?? '';
    }

    public function setI18nTitle(array $title): void
    {
        $this->title = $title;
    }

    public function getI18nTitle(): array
    {
        return $this->title;
    }

    public function setSubtitle($subtitle, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->subtitle[$locale] = $subtitle;
    }

    public function getSubtitle($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->subtitle[$locale] ?? '';
    }

    public function setI18nSubtitle(array $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function getI18nSubtitle(): array
    {
        return $this->subtitle;
    }

    public function setDescription($description, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    public function getDescription($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->description[$locale] ?? '';
    }

    public function setI18nDescription(array $description): void
    {
        $this->description = $description;
    }

    public function getI18nDescription(): array
    {
        return $this->description;
    }

    public function setComments($comments): void
    {
        $this->comments = $comments;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function setLine2($line2, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->line2[$locale] = $line2;
    }

    public function getLine2($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->line2[$locale] ?? '';
    }

    public function setI18nLine2(array $line2): void
    {
        $this->line2 = $line2;
    }

    public function getI18nLine2(): array
    {
        return $this->line2;
    }

    public function setCopyright($copyright): void
    {
        $this->copyright = $copyright;
    }

    public function getCopyright()
    {
        return $this->copyright;
    }

    public function setLicense($license)
    {
        $this->license = $license;
    }

    public function getLicense()
    {
        return $this->license;
    }

    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getDurationString(): string
    {
        if ($this->duration > 0) {
            $min = floor($this->duration / 60);
            $seg = $this->duration % 60;

            if ($seg < 10) {
                $seg = '0'.$seg;
            }

            if (0 === $min) {
                $aux = $seg."''";
            } else {
                $aux = $min."' ".$seg."''";
            }

            return $aux;
        }

        return "0''";
    }

    public function setNumview($numview): void
    {
        $this->numview = $numview;
    }

    public function incNumview(): void
    {
        ++$this->numview;
    }

    public function getNumview(): int
    {
        return $this->numview;
    }

    public function setSeries(Series $series): void
    {
        $this->series = $series;
        // NOTE: This field is for MongoDB Search Index purposes.
        //       Do not use this field and do not create setter and/or getter.
        if (!$series->isHide()) {
            $this->seriesTitle = $series->getI18nTitle();
        } else {
            $this->seriesTitle = [];
        }
    }

    public function getSeries()
    {
        // WORKAROUND: get the object series is it's hidden and the MongoDB filter is enabled.
        try {
            $this->series->isHide();
        } catch (DocumentNotFoundException $e) {
        }

        return $this->series;
    }

    public function getSeriesTitle($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->seriesTitle[$locale] ?? '';
    }

    public function setEmbeddedBroadcast(EmbeddedBroadcast $embeddedBroadcast): void
    {
        $this->embeddedBroadcast = $embeddedBroadcast;
    }

    public function getEmbeddedEvent()
    {
        return $this->embeddedEvent;
    }

    public function setEmbeddedEvent(EmbeddedEvent $embeddedEvent): void
    {
        $this->embeddedEvent = $embeddedEvent;
    }

    public function getEmbeddedSegments()
    {
        return $this->embeddedSegments;
    }

    public function setEmbeddedSegments($embeddedSegments): void
    {
        $this->embeddedSegments = $embeddedSegments;
    }

    public function addEmbeddedSegment(EmbeddedSegment $embeddedSegment): void
    {
        $this->embeddedSegments[] = $embeddedSegment;
    }

    public function removeEmbeddedSegment(EmbeddedSegment $embeddedSegment): bool
    {
        foreach ($this->embeddedSegments as $segment) {
            if ($segment->getId() === $embeddedSegment->getId()) {
                $removed = $this->embeddedSegments->removeElement($embeddedSegment);
                $this->embeddedSegments = new ArrayCollection(array_values($this->embeddedSegments->toArray()));

                return $removed;
            }
        }

        return false;
    }

    public function getEmbeddedBroadcastNotNull(): EmbeddedBroadcast
    {
        if ($this->embeddedBroadcast) {
            return $this->embeddedBroadcast;
        }

        return new EmbeddedBroadcast();
    }

    public function getEmbeddedBroadcast()
    {
        return $this->embeddedBroadcast;
    }

    public function isPublicEmbeddedBroadcast(): bool
    {
        return !$this->embeddedBroadcast || EmbeddedBroadcast::TYPE_PUBLIC === $this->embeddedBroadcast->getType();
    }

    public function setEmbeddedSocial(EmbeddedSocial $embeddedSocial): void
    {
        $this->embeddedSocial = $embeddedSocial;
    }

    public function getEmbeddedSocial()
    {
        return $this->embeddedSocial;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setTags($tags): void
    {
        $this->tags = $tags;
    }

    public function addTag($tag): bool
    {
        if (!$this->containsTag($tag)) {
            $embedTag = EmbeddedTag::getEmbeddedTag($this->tags, $tag);
            $this->tags[] = $embedTag;

            return true;
        }

        return false;
    }

    public function removeTag(TagInterface $tagToRemove): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getCod() === $tagToRemove->getCod()) {
                return $this->tags->removeElement($tag);
            }
        }

        return false;
    }

    public function containsTag(TagInterface $tagToCheck): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getCod() === $tagToCheck->getCod()) {
                return true;
            }
        }

        return false;
    }

    public function containsTagWithCod($tagCod): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getCod() === $tagCod) {
                return true;
            }
        }

        return false;
    }

    public function containsAllTags(array $tags): bool
    {
        foreach ($tags as $tag) {
            if (!$this->containsTag($tag)) {
                return false;
            }
        }

        return true;
    }

    public function containsAllTagsWithCodes(array $tagCodes): bool
    {
        foreach ($tagCodes as $tagCode) {
            if (!$this->containsTagWithCod($tagCode)) {
                return false;
            }
        }

        return true;
    }

    public function containsAnyTag(array $tags): bool
    {
        foreach ($tags as $tag) {
            if ($this->containsTag($tag)) {
                return true;
            }
        }

        return false;
    }

    public function containsAnyTagWithCodes(array $tagCodes): bool
    {
        foreach ($tagCodes as $tagCode) {
            if ($this->containsTagWithCod($tagCode)) {
                return true;
            }
        }

        return false;
    }

    public function addTrack(Track $track): void
    {
        $this->tracks->add($track);

        if ($track->metadata() instanceof VideoAudio && $track->metadata()->duration() > $this->getDuration()) {
            $this->setDuration($track->metadata()->duration());
        }
    }

    public function addDocument(Document $document): void
    {
        $this->documents->add($document);
    }

    public function addExternal(External $external): void
    {
        $this->external->add($external);
    }

    public function addImage(Image $image): void
    {
        $this->images->add($image);
    }

    public function removeTrack(Track $track): void
    {
        $this->tracks->removeElement($track);

        $this->updateDuration();
    }

    public function removeDocument(Document $document): void
    {
        $this->removeMediaById($document->id());
    }

    public function removeImage(Image $image): void
    {
        $this->removeMediaById($image->id());
    }

    public function removeTrackById($trackId): void
    {
        $this->tracks = $this->tracks->filter(function (Track $track) use ($trackId) {
            return $track->id() !== $trackId;
        });

        $this->updateDuration();
    }

    public function removeMediaById(string $id)
    {
        $this->tracks = $this->tracks->filter(function (Track $media) use ($id) {
            return $media->id() !== $id;
        });

        $this->images = $this->images->filter(function (Image $media) use ($id) {
            return $media->id() !== $id;
        });

        $this->documents = $this->documents->filter(function (Document $media) use ($id) {
            return $media->id() !== $id;
        });
    }

    public function removeAllMedias(): void
    {
        $this->tracks = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->external = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function upTrackById($trackId): void
    {
        $this->reorderTrackById($trackId);
    }

    public function downTrackById($trackId): void
    {
        $this->reorderTrackById($trackId, false);
    }

    public function containsTrack(Track $track): bool
    {
        return $this->tracks->contains($track);
    }

    public function getMedias(): array
    {
        return array_merge($this->tracks->toArray(), $this->documents->toArray(), $this->external->toArray(), $this->images->toArray());
    }

    public function getMediasWithoutExternal(): array
    {
        return array_merge($this->tracks->toArray(), $this->documents->toArray(), $this->images->toArray());
    }

    /**
     * Deprecated Use method tracks instead getTracks.
     */
    public function getTracks()
    {
        return $this->tracks();
    }

    public function tracks()
    {
        return $this->tracks;
    }

    public function documents()
    {
        return $this->documents;
    }

    public function external()
    {
        return $this->external;
    }

    public function images()
    {
        return $this->images;
    }

    /**
     * Deprecated use getMediaById instead.
     *
     * @param mixed $mediaId
     */
    public function getTrackById($mediaId)
    {
        return $this->getMediaById($mediaId);
    }

    public function getMediaById(?string $mediaId)
    {
        foreach ($this->getMedias() as $media) {
            if ($media->id() === $mediaId) {
                return $media;
            }
        }

        return null;
    }

    public function getTracksWithTag($tag): array
    {
        $r = [];

        foreach ($this->getMedias() as $track) {
            if ($track->tags()->contains($tag)) {
                $r[] = $track;
            }
        }

        return $r;
    }

    public function getTrackWithTag($tag)
    {
        foreach ($this->getMedias() as $track) {
            if ($track->tags()->contains($tag)) {
                return $track;
            }
        }

        return null;
    }

    public function getTracksWithAllTags(array $tags): array
    {
        $r = [];

        foreach ($this->getMedias() as $track) {
            if ($track->tags()->containsAllTags($tags)) {
                $r[] = $track;
            }
        }

        return $r;
    }

    public function getTrackWithAllTags(array $tags)
    {
        foreach ($this->getMedias() as $track) {
            if ($track->tags()->containsAllTags($tags)) {
                return $track;
            }
        }

        return null;
    }

    public function getTracksWithAnyTag(array $tags): array
    {
        $r = [];

        foreach ($this->getMedias() as $track) {
            if ($track->tags()->containsAnyTag($tags)) {
                $r[] = $track;
            }
        }

        return $r;
    }

    public function getTrackWithAnyTag(array $tags)
    {
        foreach ($this->getMedias() as $track) {
            if ($track->tags()->containsAnyTag($tags)) {
                return $track;
            }
        }

        return null;
    }

    public function getRealDuration(): int
    {
        $master = $this->getMaster();

        if (!$master instanceof Track) {
            return 0;
        }

        try {
            $trackDuration = $master->metadata()->duration();
        } catch (\Exception $exception) {
            return 0;
        }

        if ($this->getDuration() < $master->metadata()->duration()) {
            return $master->metadata()->duration();
        }

        return $this->getDuration();
    }

    public function getMaster($any = true): ?MediaInterface
    {
        $master = $this->getTrackWithTag('master');
        if ($master) {
            return $master;
        }

        $isAudio = $this->isOnlyAudio();

        foreach ($this->getMedias() as $track) {
            if (($isAudio && $track->metadata()->isOnlyAudio()) || (!$isAudio && !$track->metadata()->isOnlyAudio())) {
                return $track;
            }
        }

        return null;
    }

    public function getDisplayTrack()
    {
        return $this->isOnlyAudio() ? $this->getFilteredTrackWithTags(['display']) : $this->getFilteredTrackWithTags(['display'], [], ['audio']);
    }

    public function getFilteredTracksWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = [], $all = true): array
    {
        $r = [];

        foreach ($this->tracks as $track) {
            if ($all && $track->isHide()) {
                continue;
            }
            if ($any_tags && !$track->tags()->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$track->tags()->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $track->tags()->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $track->tags()->containsAllTags($not_all_tags)) {
                continue;
            }

            $r[] = $track;
        }

        return $r;
    }

    public function getFilteredTrackWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = [], $all = true)
    {
        foreach ($this->getMedias() as $track) {
            if ($all && $track->isHide()) {
                continue;
            }
            if ($any_tags && !$track->tags()->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$track->tags()->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $track->tags()->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $track->tags()->containsAllTags($not_all_tags)) {
                continue;
            }

            return $track;
        }

        return null;
    }

    public function getPeople(): array
    {
        $aux = [];

        foreach ($this->people as $role) {
            foreach ($role->getPeople() as $person) {
                if (!in_array($person, $aux, true)) {
                    $aux[] = $person;
                }
            }
        }

        return $aux;
    }

    public function getAllEmbeddedPeopleByPerson(PersonInterface $person): array
    {
        $aux = [];

        foreach ($this->people as $role) {
            foreach ($role->getPeople() as $embeddedPerson) {
                if ($embeddedPerson->getId() === $person->getId()) {
                    $aux[] = $embeddedPerson;
                }
            }
        }

        return $aux;
    }

    public function getAllEmbeddedRolesByPerson(PersonInterface $person): array
    {
        $aux = [];

        foreach ($this->people as $embeddedRole) {
            foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                if ($embeddedPerson->getId() === $person->getId()) {
                    $aux[] = $embeddedRole;

                    break;
                }
            }
        }

        return $aux;
    }

    public function containsPerson(PersonInterface $person): bool
    {
        foreach ($this->getPeople() as $embeddedPerson) {
            if ($person->getId() === $embeddedPerson->getId()) {
                return true;
            }
        }

        return false;
    }

    public function containsPersonWithRole(PersonInterface $person, RoleInterface $role): bool
    {
        foreach ($this->getPeopleByRole($role, true) as $embeddedPerson) {
            if ($person->getId() === $embeddedPerson->getId()) {
                return true;
            }
        }

        return false;
    }

    public function containsPersonWithAllRoles(PersonInterface $person, array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->containsPersonWithRole($person, $role)) {
                return false;
            }
        }

        return true;
    }

    public function containsPersonWithAnyRole(PersonInterface $person, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->containsPersonWithRole($person, $role)) {
                return true;
            }
        }

        return false;
    }

    public function getPeopleByRole(?RoleInterface $role = null, $always = false): array
    {
        return $this->getPeopleByRoleCod($role ? $role->getCod() : null, $always);
    }

    public function getPeopleByRoleCod($roleCod = null, $always = false): array
    {
        $aux = [];

        if (null !== $roleCod) {
            foreach ($this->people as $embeddedRole) {
                if ($roleCod === $embeddedRole->getCod()) {
                    if ($always || $embeddedRole->getDisplay()) {
                        foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                            $aux[] = $embeddedPerson;
                        }
                    }

                    break;
                }
            }
        } else {
            foreach ($this->people as $embeddedRole) {
                if ($always || $embeddedRole->getDisplay()) {
                    foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                        if (!in_array($embeddedPerson, $aux, true)) {
                            $aux[] = $embeddedPerson;
                        }
                    }
                }
            }
        }

        return $aux;
    }

    public function addPersonWithRole(PersonInterface $person, RoleInterface $role): void
    {
        if (!$this->containsPersonWithRole($person, $role)) {
            if ($embeddedRole = $this->getEmbeddedRole($role)) {
                $embeddedRole->addPerson($person);
            } else {
                $embeddedRole = $this->createEmbeddedRole($role);
                $embeddedRole->addPerson($person);
                $this->people[] = $embeddedRole;
            }
        }
    }

    public function removePersonWithRole(PersonInterface $person, RoleInterface $role): bool
    {
        if (!$this->containsPersonWithRole($person, $role)) {
            return false;
        }

        $embeddedRole = $this->getEmbeddedRole($role);

        $hasRemoved = $embeddedRole->removePerson($person);

        if (0 === (is_countable($embeddedRole->getPeople()) ? count($embeddedRole->getPeople()) : 0)) {
            $this->people->removeElement($embeddedRole);
        }

        return $hasRemoved;
    }

    public function getPersonWithRole($person, $role): ?PersonInterface
    {
        if ($this->containsPersonWithRole($person, $role)) {
            return $this->getEmbeddedRole($role)->getEmbeddedPerson($person);
        }

        return null;
    }

    public function upPersonWithRole($person, $role): void
    {
        $this->reorderPersonWithRole($person, $role);
    }

    public function downPersonWithRole($person, $role): void
    {
        $this->reorderPersonWithRole($person, $role, false);
    }

    public function reorderPersonWithRole(PersonInterface $person, $role, $up = true): void
    {
        $people = array_values($this->getPeopleByRole($role, true));
        $this->getEmbeddedRole($role)->getPeople()->clear();

        $out = [];
        foreach ($people as $key => $embeddedPerson) {
            if ($person->getId() === $embeddedPerson->getId()) {
                $out[($key * 10) + ($up ? -11 : 11)] = $embeddedPerson;
            } else {
                $out[$key * 10] = $embeddedPerson;
            }
        }

        ksort($out);
        foreach ($out as $embeddedPerson) {
            $this->getEmbeddedRole($role)->addPerson($embeddedPerson);
        }
    }

    public function getEmbeddedRole(RoleInterface $role): ?RoleInterface
    {
        foreach ($this->people as $embeddedRole) {
            if ($role->getCod() === $embeddedRole->getCod()) {
                return $embeddedRole;
            }
        }

        return null;
    }

    public function createEmbeddedRole(RoleInterface $role): RoleInterface
    {
        if ($role instanceof EmbeddedRole) {
            return $role;
        }

        return new EmbeddedRole($role);
    }

    public function getRoles()
    {
        return $this->people;
    }

    public function containsGroup(Group $group): bool
    {
        return $this->groups->contains($group);
    }

    public function addGroup(Group $group): void
    {
        $this->groups->add($group);
    }

    public function removeGroup(Group $group): void
    {
        $this->groups->removeElement($group);
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function isOnlyAudio(): bool
    {
        return self::TYPE_AUDIO === $this->type;
    }

    public function getDurationInMinutesAndSeconds(): array
    {
        $minutes = floor($this->getDuration() / 60);

        $seconds = $this->getDuration() % 60;

        return [
            'minutes' => $minutes,
            'seconds' => $seconds,
        ];
    }

    public function setDurationInMinutesAndSeconds($durationInMinutesAndSeconds): void
    {
        if ((!empty($durationInMinutesAndSeconds['minutes'])) && (!empty($durationInMinutesAndSeconds['seconds']))) {
            $this->duration = ($durationInMinutesAndSeconds['minutes'] * 60) + $durationInMinutesAndSeconds['seconds'];
        }
    }

    public function isMultistream(): bool
    {
        $presenterTracks = $this->getFilteredTracksWithTags(['presenter/delivery']);
        $presentationTracks = $this->getFilteredTracksWithTags(['presentation/delivery']);

        return $presenterTracks && $presentationTracks;
    }

    public function setTextIndex($textindex): void
    {
        $this->textindex = $textindex;
    }

    public function getTextIndex(): array
    {
        return $this->textindex;
    }

    public function setSecondaryTextIndex($secondarytextindex): void
    {
        $this->secondarytextindex = $secondarytextindex;
    }

    public function getSecondaryTextIndex(): array
    {
        return $this->secondarytextindex;
    }

    public function setHead(bool $isHead): void
    {
        $this->head = $isHead;
    }

    public function isHead(): bool
    {
        return $this->head;
    }

    public function setTail(bool $isTail): void
    {
        $this->tail = $isTail;
    }

    public function isTail(): bool
    {
        return $this->tail;
    }

    /**
     * Reorder track by id.
     *
     * @param string $trackId
     * @param bool   $up
     */
    private function reorderTrackById($trackId, $up = true)
    {
        $snapshot = array_values($this->tracks->toArray());
        $this->tracks->clear();

        $out = [];
        foreach ($snapshot as $key => $track) {
            if ($track->id() === $trackId) {
                $out[($key * 10) + ($up ? -11 : 11)] = $track;
            } else {
                $out[$key * 10] = $track;
            }
        }

        ksort($out);
        foreach ($out as $track) {
            $this->tracks->add($track);
        }
    }

    private function updateDuration(): void
    {
        if (0 === count($this->tracks)) {
            $this->setDuration(0);

            return;
        }

        $trackMinDuration = $this->tracks->first()->metadata()->duration();
        foreach ($this->tracks as $mmTrack) {
            if ($mmTrack->metadata()->duration() < $trackMinDuration) {
                $trackMinDuration = $mmTrack->metadata()->duration();
            }
        }

        $minDuration = $this->getDuration();
        if ($minDuration > $trackMinDuration) {
            $this->setDuration($trackMinDuration);
        }
    }
}
