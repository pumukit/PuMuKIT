<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\MultimediaObjectRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(name="text_index", keys={"textindex.text"="text", "secondarytextindex.text"="text"}, options={"language_override"="indexlanguage", "default_language"="none", "weights"={"textindex.text"=10, "secondarytextindex.text"=1}})
 * })
 */
class MultimediaObject
{
    use Traits\Keywords;
    use Traits\Properties;
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
    ];

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="int")
     * @MongoDB\UniqueIndex()
     */
    private $numerical_id;

    /**
     * @MongoDB\Field(type="int")
     * @MongoDB\Index
     */
    private $type;

    /**
     * @MongoDB\Field(type="string")
     * @MongoDB\Index
     */
    private $secret;

    /**
     * @MongoDB\ReferenceOne(targetDocument=Series::class, storeAs="id", inversedBy="multimedia_object", cascade={"persist"})
     * @Gedmo\SortableGroup
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
     * @MongoDB\ReferenceMany(targetDocument=Group::class, storeAs="id", sort={"key":1}, strategy="setArray", cascade={"persist","remove"})
     */
    private $groups;

    /**
     * @MongoDB\Field(type="int")
     * @Gedmo\SortablePosition
     */
    private $rank;

    /**
     * @MongoDB\Field(type="int")
     */
    private $status = self::STATUS_NEW;

    /**
     * @MongoDB\Field(type="date")
     * @MongoDB\Index
     */
    private $record_date;

    /**
     * @MongoDB\Field(type="date")
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
     * @MongoDB\Field(type="int", strategy="increment" )
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
        $this->secret = base_convert(sha1(uniqid((string) mt_rand(), true)), 16, 36);
        $this->tracks = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->people = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->type = self::TYPE_UNKNOWN;

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

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function resetSecret(): string
    {
        $this->secret = base_convert(sha1(uniqid((string) mt_rand(), true)), 16, 36);

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
        } catch (\Doctrine\ODM\MongoDB\DocumentNotFoundException $e) {
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
        if (!($this->containsTag($tag))) {
            $embedTag = EmbeddedTag::getEmbeddedTag($this->tags, $tag);
            $this->tags[] = $embedTag;

            return true;
        }

        return false;
    }

    public function removeTag($tagToRemove): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getCod() === $tagToRemove->getCod()) {
                return $this->tags->removeElement($tag);
            }
        }

        return false;
    }

    public function containsTag($tagToCheck): bool
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
            if (!($this->containsTag($tag))) {
                return false;
            }
        }

        return true;
    }

    public function containsAllTagsWithCodes(array $tagCodes): bool
    {
        foreach ($tagCodes as $tagCode) {
            if (!($this->containsTagWithCod($tagCode))) {
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

        if ($track->getDuration() > $this->getDuration()) {
            $this->setDuration($track->getDuration());
        }
    }

    public function removeTrack(Track $track): void
    {
        $this->tracks->removeElement($track);

        $this->updateDuration();
    }

    public function removeTrackById($trackId): void
    {
        $this->tracks = $this->tracks->filter(function ($track) use ($trackId) {
            return $track->getId() !== $trackId;
        });

        $this->updateDuration();
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

    public function getTracks()
    {
        return $this->tracks;
    }

    public function getTrackById($trackId)
    {
        foreach ($this->tracks as $track) {
            if ($track->getId() === $trackId) {
                return $track;
            }
        }

        return null;
    }

    public function getTracksWithTag($tag): array
    {
        $r = [];

        foreach ($this->tracks as $track) {
            if ($track->containsTag($tag)) {
                $r[] = $track;
            }
        }

        return $r;
    }

    public function getTrackWithTag($tag)
    {
        foreach ($this->tracks as $track) {
            if ($track->containsTag($tag)) {
                return $track;
            }
        }

        return null;
    }

    public function getTracksWithAllTags(array $tags): array
    {
        $r = [];

        foreach ($this->tracks as $track) {
            if ($track->containsAllTags($tags)) {
                $r[] = $track;
            }
        }

        return $r;
    }

    public function getTrackWithAllTags(array $tags)
    {
        foreach ($this->tracks as $track) {
            if ($track->containsAllTags($tags)) {
                return $track;
            }
        }

        return null;
    }

    public function getTracksWithAnyTag(array $tags): array
    {
        $r = [];

        foreach ($this->tracks as $track) {
            if ($track->containsAnyTag($tags)) {
                $r[] = $track;
            }
        }

        return $r;
    }

    public function getTrackWithAnyTag(array $tags)
    {
        foreach ($this->tracks as $track) {
            if ($track->containsAnyTag($tags)) {
                return $track;
            }
        }

        return null;
    }

    public function getRealDuration(): int
    {
        $master = $this->getMaster();

        if (!$master) {
            return 0;
        }

        if ($this->getDuration() < $master->getDuration()) {
            return $master->getDuration();
        }

        return $this->getDuration();
    }

    public function getMaster($any = true)
    {
        $master = $this->getTrackWithTag('master');

        if ($master || !$any) {
            return $master;
        }

        $isAudio = $this->isOnlyAudio();

        foreach ($this->tracks as $track) {
            if (($isAudio && $track->isOnlyAudio()) || (!$isAudio && !$track->isOnlyAudio())) {
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
            if ($all && $track->getHide()) {
                continue;
            }
            if ($any_tags && !$track->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$track->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $track->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $track->containsAllTags($not_all_tags)) {
                continue;
            }

            $r[] = $track;
        }

        return $r;
    }

    public function getFilteredTrackWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = [], $all = true)
    {
        foreach ($this->tracks as $track) {
            if ($all && $track->getHide()) {
                continue;
            }
            if ($any_tags && !$track->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$track->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $track->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $track->containsAllTags($not_all_tags)) {
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

    public function getAllEmbeddedPeopleByPerson($person): array
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

    public function getAllEmbeddedRolesByPerson($person): array
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

    public function containsPerson($person): bool
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
            if (!($this->containsPersonWithRole($person, $role))) {
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

    public function getPeopleByRole($role = null, $always = false): array
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
        if (!($this->containsPersonWithRole($person, $role))) {
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
        if (!($this->containsPersonWithRole($person, $role))) {
            return false;
        }

        $embeddedRole = $this->getEmbeddedRole($role);

        $hasRemoved = $embeddedRole->removePerson($person);

        if (0 === count($embeddedRole->getPeople())) {
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

    public function reorderPersonWithRole($person, $role, $up = true): void
    {
        $people = array_values($this->getPeopleByRole($role, true));
        $this->getEmbeddedRole($role)->getPeople()->clear();

        $out = [];
        foreach ($people as $key => $embeddedPerson) {
            if ($person->getId() === $embeddedPerson->getId()) {
                $out[($key * 10) + ($up ? -11 : 11)] = $embeddedPerson;
            } else {
                $out[($key * 10)] = $embeddedPerson;
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

    public function addGroup(Group $group): bool
    {
        return $this->groups->add($group);
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
            if ($track->getId() === $trackId) {
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

        $trackMinDuration = $this->tracks->first()->getDuration();
        foreach ($this->tracks as $mmTrack) {
            if ($mmTrack->getDuration() < $trackMinDuration) {
                $trackMinDuration = $mmTrack->getDuration();
            }
        }

        $minDuration = $this->getDuration();
        if ($minDuration > $trackMinDuration) {
            $this->setDuration($trackMinDuration);
        }
    }
}
