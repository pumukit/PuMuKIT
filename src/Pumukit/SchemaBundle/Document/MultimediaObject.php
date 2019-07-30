<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Pumukit\SchemaBundle\Document\MultimediaObject.
 *
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

    const STATUS_PUBLISHED = 0;
    const STATUS_BLOCKED = 1;
    const STATUS_HIDDEN = 2;
    const STATUS_NEW = -1;
    const STATUS_PROTOTYPE = -2;

    const TYPE_UNKNOWN = 0;
    const TYPE_VIDEO = 1;
    const TYPE_AUDIO = 2;
    const TYPE_EXTERNAL = 3;
    const TYPE_LIVE = 4;

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
     * @var int
     * @MongoDB\Id
     */
    private $id;

    /**
     * Numerical identifier.
     *
     * @var int
     * @MongoDB\Field(type="int")
     * @MongoDB\UniqueIndex(safe=1)
     */
    private $numerical_id;

    /**
     * @var int
     * @MongoDB\Field(type="int")
     * @MongoDB\Index
     */
    private $type;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     * @MongoDB\Index
     */
    private $secret;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Series", storeAs="id", inversedBy="multimedia_object", cascade={"persist"})
     * @Gedmo\SortableGroup
     * @MongoDB\Index
     */
    private $series;

    /**
     * NOTE: This field is for MongoDB Search Index purposes.
     *       Do not use this field and do not create setter and/or getter.
     *
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $seriesTitle = ['en' => ''];

    /**
     * @var Broadcast
     *
     * @deprecated in version 2.3
     * use EmbeddedBroadcast instead
     * @MongoDB\ReferenceOne(targetDocument="Broadcast", inversedBy="multimedia_object", storeAs="id", cascade={"persist"})
     */
    private $broadcast;

    /**
     * @var EmbeddedBroadcast
     * @MongoDB\EmbedOne(targetDocument="EmbeddedBroadcast")
     */
    private $embeddedBroadcast;

    /**
     * @var EmbeddedEvent
     * @MongoDB\EmbedOne(targetDocument="EmbeddedEvent")
     */
    private $embeddedEvent;

    /**
     * @var ArrayCollection
     * @MongoDB\EmbedMany(targetDocument="EmbeddedSegment")
     */
    private $embeddedSegments;

    /**
     * @var EmbeddedSocial
     * @MongoDB\EmbedOne(targetDocument="EmbeddedSocial")
     */
    private $embeddedSocial;

    /**
     * @var ArrayCollection
     * @MongoDB\EmbedMany(targetDocument="EmbeddedTag")
     */
    private $tags;

    /**
     * @var ArrayCollection
     * @MongoDB\EmbedMany(targetDocument="Track")
     */
    private $tracks;

    /**
     * @var ArrayCollection
     * @MongoDB\ReferenceMany(targetDocument="Group", storeAs="id", sort={"key":1}, strategy="setArray")
     */
    private $groups;

    /**
     * @var int
     * @MongoDB\Field(type="int")
     * @Gedmo\SortablePosition
     */
    private $rank;

    /**
     * @var int
     * @MongoDB\Field(type="int")
     */
    private $status = self::STATUS_NEW;

    /**
     * @var \DateTime
     * @MongoDB\Field(type="date")
     * @MongoDB\Index
     */
    private $record_date;

    /**
     * @var \DateTime
     * @MongoDB\Field(type="date")
     * @MongoDB\Index
     */
    private $public_date;

    /**
     * @var array
     * @MongoDB\Field(type="raw")
     */
    private $title = ['en' => ''];

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $subtitle = ['en' => ''];

    /**
     * @var array
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $comments;

    /**
     * @var array
     * @MongoDB\Field(type="raw")
     */
    private $line2 = ['en' => ''];

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $copyright;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $license;

    /**
     * @var int
     * @MongoDB\Field(type="int")
     */
    private $duration = 0;

    /**
     * @var int
     * @MongoDB\Field(type="increment")
     */
    private $numview = 0;

    /**
     * @var ArrayCollection
     * @MongoDB\EmbedMany(targetDocument="EmbeddedRole")
     */
    private $people;

    /**
     * @var array
     * @MongoDB\Field(type="raw")
     */
    private $textindex = [];

    /**
     * @var array
     * @MongoDB\Field(type="raw")
     */
    private $secondarytextindex = [];

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var string
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->secret = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
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

    public function __toString()
    {
        return $this->getTitle();
    }

    /**
     * @return bool
     */
    public function isCollection()
    {
        return false;
    }

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get numerical id.
     *
     * @return int
     */
    public function getNumericalID()
    {
        return $this->numerical_id;
    }

    /**
     * Set numerical id.
     *
     * @param mixed $numericalID
     *
     * @return int
     */
    public function setNumericalID($numericalID)
    {
        return $this->numerical_id = $numericalID;
    }

    /**
     * Get secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Resets secret.
     *
     * @return string
     */
    public function resetSecret()
    {
        $this->secret = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);

        return $this->secret;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set rank.
     *
     * @param int $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    /**
     * Get rank.
     *
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @return bool
     */
    public function isLive()
    {
        return self::TYPE_LIVE === $this->type;
    }

    /**
     * Set type.
     *
     * @param $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     *
     * @return string
     */
    public function getStringType($type)
    {
        return self::$typeTexts[$type];
    }

    /**
     * Set status.
     *
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     *
     * @return string
     */
    public function getStringStatus($status)
    {
        return self::$statusTexts[$status];
    }

    /**
     * Helper function to know if is published.
     *
     * @return bool
     */
    public function isPublished()
    {
        return self::STATUS_PUBLISHED === $this->getStatus();
    }

    /**
     * Helper function to know if is bloqued.
     *
     * @return bool
     */
    public function isBlocked()
    {
        return self::STATUS_BLOCKED === $this->getStatus();
    }

    /**
     * Helper function to know if is hidden.
     *
     * @return bool
     */
    public function isHidden()
    {
        return self::STATUS_HIDDEN === $this->getStatus();
    }

    /**
     * Helper function to know if is the status a prototype.
     *
     * @return bool
     */
    public function isPrototype()
    {
        return self::STATUS_PROTOTYPE === $this->getStatus();
    }

    /**
     * Set record_date.
     *
     * @param \DateTime $recordDate
     */
    public function setRecordDate($recordDate)
    {
        $this->record_date = $recordDate;
    }

    /**
     * Get record_date.
     *
     * @return \DateTime
     */
    public function getRecordDate()
    {
        return $this->record_date;
    }

    /**
     * Set public_date.
     *
     * @param \DateTime $publicDate
     */
    public function setPublicDate($publicDate)
    {
        $this->public_date = $publicDate;
    }

    /**
     * Get public_date.
     *
     * @return \DateTime
     */
    public function getPublicDate()
    {
        return $this->public_date;
    }

    /**
     * Set title.
     *
     * @param string      $title
     * @param null|string $locale
     */
    public function setTitle($title, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->title[$locale] = $title;
    }

    /**
     * Get title.
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getTitle($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->title[$locale])) {
            return '';
        }

        return $this->title[$locale];
    }

    /**
     * Set I18n title.
     *
     * @param array $title
     */
    public function setI18nTitle(array $title)
    {
        $this->title = $title;
    }

    /**
     * Get I18n title.
     *
     * @return array
     */
    public function getI18nTitle()
    {
        return $this->title;
    }

    /**
     * Set subtitle.
     *
     * @param string      $subtitle
     * @param null|string $locale
     */
    public function setSubtitle($subtitle, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->subtitle[$locale] = $subtitle;
    }

    /**
     * Get subtitle.
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getSubtitle($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->subtitle[$locale])) {
            return '';
        }

        return $this->subtitle[$locale];
    }

    /**
     * Set I18n subtitle.
     *
     * @param array $subtitle
     */
    public function setI18nSubtitle(array $subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * Get I18n subtitle.
     *
     * @return string
     */
    public function getI18nSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Set description.
     *
     * @param string      $description
     * @param null|string $locale
     */
    public function setDescription($description, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    /**
     * Get description.
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getDescription($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->description[$locale])) {
            return '';
        }

        return $this->description[$locale];
    }

    /**
     * Set I18n description.
     *
     * @param array $description
     */
    public function setI18nDescription(array $description)
    {
        $this->description = $description;
    }

    /**
     * Get I18n description.
     *
     * @return array
     */
    public function getI18nDescription()
    {
        return $this->description;
    }

    /**
     * Set comments.
     *
     * @param string $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * Get comments.
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set line2.
     *
     * @param string      $line2
     * @param null|string $locale
     */
    public function setLine2($line2, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->line2[$locale] = $line2;
    }

    /**
     * Get line2.
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getLine2($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->line2[$locale])) {
            return '';
        }

        return $this->line2[$locale];
    }

    /**
     * Set I18n line2.
     *
     * @param array $line2
     */
    public function setI18nLine2(array $line2)
    {
        $this->line2 = $line2;
    }

    /**
     * Get I18n line2.
     *
     * @return array
     */
    public function getI18nLine2()
    {
        return $this->line2;
    }

    /**
     * Set copyright.
     *
     * @param string $copyright
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;
    }

    /**
     * Get copyright.
     *
     * @return string
     */
    public function getCopyright()
    {
        return $this->copyright;
    }

    /**
     * Set license.
     *
     * @param string $license
     */
    public function setLicense($license)
    {
        $this->license = $license;
    }

    /**
     * Get license.
     *
     * @return string
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * Set duration.
     *
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * Get duration.
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Get duration in string format.
     *
     * @return string
     */
    public function getDurationString()
    {
        if ($this->duration > 0) {
            $min = floor($this->duration / 60);
            $seg = $this->duration % 60;

            if ($seg < 10) {
                $seg = '0'.$seg;
            }

            if (0 == $min) {
                $aux = $seg."''";
            } else {
                $aux = $min."' ".$seg."''";
            }

            return $aux;
        }

        return "0''";
    }

    /**
     * Set numview.
     *
     * @param int $numview
     */
    public function setNumview($numview)
    {
        $this->numview = $numview;
    }

    /**
     * Increment numview.
     */
    public function incNumview()
    {
        ++$this->numview;
    }

    /**
     * Get numview.
     *
     * @return int
     */
    public function getNumview()
    {
        return $this->numview;
    }

    // End of basic setter & getters

    /**
     * Set series.
     *
     * @param Series $series
     */
    public function setSeries(Series $series)
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

    /**
     * Get series.
     *
     * @return Series
     */
    public function getSeries()
    {
        // WORKAROUND: get the object series is it's hidden and the MongoDB filter is enabled.
        try {
            $this->series->isHide();
        } catch (\Doctrine\ODM\MongoDB\DocumentNotFoundException $e) {
        }

        return $this->series;
    }

    /**
     * Get series title, only for performace use.
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getSeriesTitle($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->seriesTitle[$locale])) {
            return '';
        }

        return $this->seriesTitle[$locale];
    }

    /**
     * Set broadcast.
     *
     * @deprecated in version 2.3
     *
     * @param Broadcast $broadcast
     */
    public function setBroadcast(Broadcast $broadcast)
    {
        if (($this->broadcast instanceof Broadcast) && (self::STATUS_PROTOTYPE != $this->status)) {
            $this->broadcast->decreaseNumberMultimediaObjects();
        }
        $this->broadcast = $broadcast;
        if (self::STATUS_PROTOTYPE != $this->status) {
            $broadcast->increaseNumberMultimediaObjects();
        }
    }

    /**
     * Get broadcast.
     *
     * @deprecated in version 2.3
     *
     * @return Broadcast
     */
    public function getBroadcast()
    {
        return $this->broadcast;
    }

    /**
     * Get broadcast.
     *
     * @deprecated in version 2.3
     *
     * @return bool Broadcast
     */
    public function isPublicBroadcast()
    {
        return (bool) (!$this->broadcast || Broadcast::BROADCAST_TYPE_PUB == $this->broadcast->getBroadcastTypeId());
    }

    /**
     * Set embedded broadcast.
     *
     * @param EmbeddedBroadcast $embeddedBroadcast
     */
    public function setEmbeddedBroadcast(EmbeddedBroadcast $embeddedBroadcast)
    {
        $this->embeddedBroadcast = $embeddedBroadcast;
    }

    /**
     * Get embeddedEvent.
     *
     * @return EmbeddedEvent
     */
    public function getEmbeddedEvent()
    {
        return $this->embeddedEvent;
    }

    /**
     * Set embeddedEvent.
     *
     * @param EmbeddedEvent $embeddedEvent
     */
    public function setEmbeddedEvent(EmbeddedEvent $embeddedEvent)
    {
        $this->embeddedEvent = $embeddedEvent;
    }

    /**
     * @return ArrayCollection
     */
    public function getEmbeddedSegments()
    {
        return $this->embeddedSegments;
    }

    /**
     * @param array $embeddedSegments
     */
    public function setEmbeddedSegments(array $embeddedSegments)
    {
        $this->embeddedSegments = $embeddedSegments;
    }

    /**
     * @param EmbeddedSegment $embeddedSegment
     */
    public function addEmbeddedSegment(EmbeddedSegment $embeddedSegment)
    {
        $this->embeddedSegments[] = $embeddedSegment;
    }

    /**
     * @param EmbeddedSegment $embeddedSegment
     *
     * @return bool
     */
    public function removeEmbeddedSegment(EmbeddedSegment $embeddedSegment)
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

    /**
     * Get embeddedBroadcast.
     *
     * @return EmbeddedBroadcast
     */
    public function getEmbeddedBroadcastNotNull()
    {
        if ($this->embeddedBroadcast) {
            return $this->embeddedBroadcast;
        }

        return new EmbeddedBroadcast();
    }

    /**
     * Get embeddedBroadcast.
     *
     * @return null|EmbeddedBroadcast
     */
    public function getEmbeddedBroadcast()
    {
        return $this->embeddedBroadcast;
    }

    /**
     * Is public embedded broadcast.
     *
     * @return bool Broadcast
     */
    public function isPublicEmbeddedBroadcast()
    {
        return (bool) (!$this->embeddedBroadcast || EmbeddedBroadcast::TYPE_PUBLIC === $this->embeddedBroadcast->getType());
    }

    /**
     * Set embedded social.
     *
     * @param EmbeddedSocial $embeddedSocial
     */
    public function setEmbeddedSocial(EmbeddedSocial $embeddedSocial)
    {
        $this->embeddedSocial = $embeddedSocial;
    }

    /**
     * Get embedded social.
     *
     * @return EmbeddedSocial
     */
    public function getEmbeddedSocial()
    {
        return $this->embeddedSocial;
    }

    // Start tag section. Caution: MultimediaObject tags are Tag objects, not strings.

    /**
     * Get tags.
     *
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set tags.
     *
     * @param array $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Add tag.
     * The original string tag logic used array_unique to avoid tag duplication.
     *
     * @param $tag Tag|EmbeddedTag
     *
     * @return bool
     */
    public function addTag($tag)
    {
        if (!($this->containsTag($tag))) {
            $embedTag = EmbeddedTag::getEmbeddedTag($this->tags, $tag);
            $this->tags[] = $embedTag;

            return true;
        }

        return false;
    }

    /**
     * Remove tag.
     * The original string tag logic used array_search to seek the tag element in array.
     * This function uses doctrine2 arrayCollection contains function instead.
     *
     * @param EmbeddedTag|Tag $tagToRemove
     *
     * @return bool TRUE if this multimedia_object contained the specified tag, FALSE otherwise
     */
    public function removeTag($tagToRemove)
    {
        foreach ($this->tags as $tag) {
            if ($tag->getCod() == $tagToRemove->getCod()) {
                return $this->tags->removeElement($tag);
            }
        }

        return false;
    }

    /**
     * Contains tag.
     * The original string tag logic used in_array to check it.
     * This function uses doctrine2 arrayCollection contains function instead.
     *
     * @param EmbeddedTag|Tag $tagToCheck
     *
     * @return bool TRUE if this multimedia_object contained the specified tag, FALSE otherwise
     */
    public function containsTag($tagToCheck)
    {
        foreach ($this->tags as $tag) {
            if ($tag->getCod() == $tagToCheck->getCod()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Contains tag with cod.
     *
     * @param string $tagCod
     *
     * @return bool TRUE if this multimedia_object contained the specified tag, FALSE otherwise
     */
    public function containsTagWithCod($tagCod)
    {
        foreach ($this->tags as $tag) {
            if ($tag->getCod() == $tagCod) {
                return true;
            }
        }

        return false;
    }

    /**
     * Contains all tags
     * The original string tag logic used array_intersect and count to check it.
     * This function uses doctrine2 arrayCollection contains function instead.
     *
     * @param array $tags
     *
     * @return bool TRUE if this multimedia_object contained all tags, FALSE otherwise
     */
    public function containsAllTags(array $tags)
    {
        foreach ($tags as $tag) {
            if (!($this->containsTag($tag))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Contains all tags with codes
     * The original string tag logic used array_intersect and count to check it.
     * This function uses doctrine2 arrayCollection contains function instead.
     *
     * @param array $tagCodes
     *
     * @return bool TRUE if this multimedia_object contained all tags, FALSE otherwise
     */
    public function containsAllTagsWithCodes(array $tagCodes)
    {
        foreach ($tagCodes as $tagCode) {
            if (!($this->containsTagWithCod($tagCode))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Contains any tags
     * The original string tag logic used array_intersect and count to check it.
     * This function uses doctrine2 arrayCollection contains function instead.
     *
     * @param array $tags
     *
     * @return bool TRUE if this multimedia_object contained any tag of the list, FALSE otherwise
     */
    public function containsAnyTag(array $tags)
    {
        foreach ($tags as $tag) {
            if ($this->containsTag($tag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Contains any tags with codes
     * The original string tag logic used array_intersect and count to check it.
     * This function uses doctrine2 arrayCollection contains function instead.
     *
     * @param array $tagCodes
     *
     * @return bool TRUE if this multimedia_object contained any tag of the list, FALSE otherwise
     */
    public function containsAnyTagWithCodes(array $tagCodes)
    {
        foreach ($tagCodes as $tagCode) {
            if ($this->containsTagWithCod($tagCode)) {
                return true;
            }
        }

        return false;
    }

    // End of tags section

    /**
     * Add track.
     *
     * @param Track $track
     */
    public function addTrack(Track $track)
    {
        $this->tracks->add($track);

        if ($track->getDuration() > $this->getDuration()) {
            $this->setDuration($track->getDuration());
        }
    }

    /**
     * Remove track.
     *
     * @param Track $track
     */
    public function removeTrack(Track $track)
    {
        $this->tracks->removeElement($track);

        $this->updateDuration();
    }

    /**
     * Remove track by id.
     *
     * @param string $trackId
     */
    public function removeTrackById($trackId)
    {
        $this->tracks = $this->tracks->filter(function ($track) use ($trackId) {
            return $track->getId() !== $trackId;
        });

        $this->updateDuration();
    }

    /**
     * Up track by id.
     *
     * @param string $trackId
     */
    public function upTrackById($trackId)
    {
        $this->reorderTrackById($trackId, true);
    }

    /**
     * Down track by id.
     *
     * @param string $trackId
     */
    public function downTrackById($trackId)
    {
        $this->reorderTrackById($trackId, false);
    }

    /**
     * Contains track.
     *
     * @param Track $track
     *
     * @return bool
     */
    public function containsTrack(Track $track)
    {
        return $this->tracks->contains($track);
    }

    /**
     * Get tracks.
     *
     * @return ArrayCollection
     */
    public function getTracks()
    {
        return $this->tracks;
    }

    /**
     * Get track by id.
     *
     * @param $trackId
     *
     * @return null|Track
     */
    public function getTrackById($trackId)
    {
        foreach ($this->tracks as $track) {
            if ($track->getId() == $trackId) {
                return $track;
            }
        }

        return null;
    }

    /**
     * Get tracks with tag.
     *
     * @param string $tag
     *
     * @return array
     */
    public function getTracksWithTag($tag)
    {
        $r = [];

        foreach ($this->tracks as $track) {
            if ($track->containsTag($tag)) {
                $r[] = $track;
            }
        }

        return $r;
    }

    /**
     * Get track with tag.
     *
     * @param string $tag
     *
     * @return null|Track
     */
    public function getTrackWithTag($tag)
    {
        foreach ($this->tracks as $track) {
            if ($track->containsTag($tag)) {
                return $track;
            }
        }

        return null;
    }

    /**
     * Get tracks with all tags.
     *
     * @param array $tags
     *
     * @return array
     */
    public function getTracksWithAllTags(array $tags)
    {
        $r = [];

        foreach ($this->tracks as $track) {
            if ($track->containsAllTags($tags)) {
                $r[] = $track;
            }
        }

        return $r;
    }

    /**
     * Get tracks with all tags.
     *
     * @param array $tags
     *
     * @return null|Track
     */
    public function getTrackWithAllTags(array $tags)
    {
        foreach ($this->tracks as $track) {
            if ($track->containsAllTags($tags)) {
                return $track;
            }
        }

        return null;
    }

    /**
     * Get tracks with any tag.
     *
     * @param array $tags
     *
     * @return array
     */
    public function getTracksWithAnyTag(array $tags)
    {
        $r = [];

        foreach ($this->tracks as $track) {
            if ($track->containsAnyTag($tags)) {
                $r[] = $track;
            }
        }

        return $r;
    }

    /**
     * Get track with any tag.
     *
     * @param array $tags
     *
     * @return null|Track
     */
    public function getTrackWithAnyTag(array $tags)
    {
        foreach ($this->tracks as $track) {
            if ($track->containsAnyTag($tags)) {
                return $track;
            }
        }

        return null;
    }

    /**
     * Get real duration for cases with soft trimming edition.
     *
     * @return int
     */
    public function getRealDuration()
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

    /**
     * Get master track.
     *
     * @param bool $any to get only tagged tracks
     *
     * @return null|Track
     */
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

    /**
     * Get audio/video track with tag display. Get an audio track if the object is an audio.
     *
     * @return null|Track
     */
    public function getDisplayTrack()
    {
        return $this->isOnlyAudio() ? $this->getFilteredTrackWithTags(['display']) : $this->getFilteredTrackWithTags(['display'], [], ['audio']);
    }

    /**
     * Get filtered tracks with tags.
     *
     * @param array $any_tags
     * @param array $all_tags
     * @param array $not_any_tags
     * @param array $not_all_tags
     * @param bool  $all
     *
     * @return array
     */
    public function getFilteredTracksWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = [], $all = true)
    {
        $r = [];

        foreach ($this->tracks as $track) {
            if ($track->getHide() && $all) {
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

    /**
     * Get filtered track with tags.
     *
     * @param array $any_tags
     * @param array $all_tags
     * @param array $not_any_tags
     * @param array $not_all_tags
     * @param bool  $all
     *
     * @return null|Track
     */
    public function getFilteredTrackWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = [], $all = true)
    {
        foreach ($this->tracks as $track) {
            if ($track->getHide() && $all) {
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

    // End of Track getter - setter etc methods section

    // Start people section.

    /**
     * Get people.
     *
     * @return array
     */
    public function getPeople()
    {
        $aux = [];

        foreach ($this->people as $role) {
            foreach ($role->getPeople() as $person) {
                if (!in_array($person, $aux)) {
                    $aux[] = $person;
                }
            }
        }

        return $aux;
    }

    /**
     * Get all embedded people in multimedia object by person id.
     *
     * @param Person $person
     *
     * @return array
     */
    public function getAllEmbeddedPeopleByPerson($person)
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

    /**
     * Get all embedded role in multimedia object by person id.
     *
     * @param Person $person
     *
     * @return array
     */
    public function getAllEmbeddedRolesByPerson($person)
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

    /**
     * Contains EmbeddedPerson without mattering the role
     * Use containsPersonWithRole instead.
     *
     * @param EmbeddedPerson|Person $person
     *
     * @return bool TRUE if this multimedia_object contains the specified person, FALSE otherwise
     */
    public function containsPerson($person)
    {
        foreach ($this->getPeople() as $embeddedPerson) {
            if ($person->getId() == $embeddedPerson->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Contains person with role.
     *
     * @param EmbeddedPerson|Person $person
     * @param EmbeddedRole|Role     $role
     *
     * @return bool
     */
    public function containsPersonWithRole($person, $role)
    {
        foreach ($this->getPeopleByRole($role, true) as $embeddedPerson) {
            if ($person->getId() == $embeddedPerson->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Contains person with all roles.
     *
     * @param EmbeddedPerson|Person $person
     * @param array                 $roles
     *
     * @return bool
     */
    public function containsPersonWithAllRoles($person, array $roles)
    {
        foreach ($roles as $role) {
            if (!($this->containsPersonWithRole($person, $role))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Contains person with any role.
     *
     * @param EmbeddedPerson|Person $person
     * @param array                 $roles
     *
     * @return bool
     */
    public function containsPersonWithAnyRole($person, array $roles)
    {
        foreach ($roles as $role) {
            if ($this->containsPersonWithRole($person, $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get people in multimedia object by role.
     *
     * @param EmbeddedRole|Role $role
     * @param bool              $always
     *
     * @return array
     */
    public function getPeopleByRole($role = null, $always = false)
    {
        return $this->getPeopleByRoleCod($role ? $role->getCod() : null, $always);
    }

    /**
     * Get people in multimedia object by role.
     *
     * @param string $roleCod
     * @param bool   $always  to search in all the roles
     *
     * @return array
     */
    public function getPeopleByRoleCod($roleCod = null, $always = false)
    {
        $aux = [];

        if (null !== $roleCod) {
            foreach ($this->people as $embeddedRole) {
                if ($roleCod == $embeddedRole->getCod()) {
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
                        if (!in_array($embeddedPerson, $aux)) {
                            $aux[] = $embeddedPerson;
                        }
                    }
                }
            }
        }

        return $aux;
    }

    /**
     * Add Person with Role.
     *
     * @param EmbeddedPerson|Person $person
     * @param EmbeddedRole|Role     $role
     */
    public function addPersonWithRole($person, $role)
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

    /**
     * Remove Person With Role.
     *
     * @param EmbeddedPerson|Person $person
     * @param EmbeddedRole|Role     $role
     *
     * @return bool TRUE if this multimedia_object contained the specified person_in_multimedia_object, FALSE otherwise
     */
    public function removePersonWithRole($person, $role)
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

    /**
     * Get person with role.
     *
     * @param EmbeddedPerson|Person $person
     * @param EmbeddedRole|Role     $role
     *
     * @return bool|EmbeddedPerson EmbeddedPerson if found, FALSE otherwise
     */
    public function getPersonWithRole($person, $role)
    {
        if ($this->containsPersonWithRole($person, $role)) {
            return $this->getEmbeddedRole($role)->getEmbeddedPerson($person);
        }

        return false;
    }

    /**
     * Up person with role.
     *
     * @param EmbeddedPerson|Person $person
     * @param EmbeddedRole|Role     $role
     */
    public function upPersonWithRole($person, $role)
    {
        $this->reorderPersonWithRole($person, $role, true);
    }

    /**
     * Down person with role.
     *
     * @param EmbeddedPerson|Person $person
     * @param EmbeddedRole|Role     $role
     */
    public function downPersonWithRole($person, $role)
    {
        $this->reorderPersonWithRole($person, $role, false);
    }

    /**
     * Reorder person with role.
     *
     * @param EmbeddedRole|Person $person
     * @param EmbeddedRole|Role   $role
     * @param bool                $up
     */
    public function reorderPersonWithRole($person, $role, $up = true)
    {
        $people = array_values($this->getPeopleByRole($role, true));
        $this->getEmbeddedRole($role)->getPeople()->clear();

        $out = [];
        foreach ($people as $key => $embeddedPerson) {
            if ($person->getId() == $embeddedPerson->getId()) {
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

    /**
     * Get embedded role.
     *
     * @param EmbeddedRole|Role
     * @param mixed $role
     *
     * @return bool|EmbeddedRole EmbeddedRole if found, FALSE otherwise
     */
    public function getEmbeddedRole($role)
    {
        foreach ($this->people as $embeddedRole) {
            if ($role->getCod() === $embeddedRole->getCod()) {
                return $embeddedRole;
            }
        }

        return false;
    }

    /**
     * Create embedded role.
     *
     * @param EmbeddedRole|Role $role
     *
     * @return EmbeddedRole
     */
    public function createEmbeddedRole($role)
    {
        if ($role instanceof EmbeddedRole) {
            return $role;
        }
        if ($role instanceof Role) {
            return new EmbeddedRole($role);
        }

        throw new \InvalidArgumentException('Only Role or EmbeddedRole are allowed.');
    }

    /**
     * Get Roles.
     *
     * @return ArrayCollection
     */
    public function getRoles()
    {
        return $this->people;
    }

    // End of people section

    // Group section

    /**
     * Contains group.
     *
     * @param Group $group
     *
     * @return bool
     */
    public function containsGroup(Group $group)
    {
        return $this->groups->contains($group);
    }

    /**
     * add admin group.
     *
     * @param Group $group
     *
     * @return bool
     */
    public function addGroup(Group $group)
    {
        return $this->groups->add($group);
    }

    /**
     * Remove admin group.
     *
     * @param Group $group
     */
    public function removeGroup(Group $group)
    {
        $this->groups->removeElement($group);
    }

    /**
     * Get groups.
     *
     * @return ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Is only audio.
     *
     * @return bool TRUE if all tracks in multimedia object are only audio, FALSE otherwise
     */
    public function isOnlyAudio()
    {
        if (self::TYPE_AUDIO === $this->type) {
            return true;
        }

        return false;
    }

    /**
     * Get duration in minutes and seconds.
     *
     * @return array
     */
    public function getDurationInMinutesAndSeconds()
    {
        $minutes = floor($this->getDuration() / 60);

        $seconds = $this->getDuration() % 60;

        return [
            'minutes' => $minutes,
            'seconds' => $seconds,
        ];
    }

    /**
     * Set duration in minutes and seconds.
     *
     * @param array
     * @param mixed $durationInMinutesAndSeconds
     */
    public function setDurationInMinutesAndSeconds($durationInMinutesAndSeconds)
    {
        if ((!empty($durationInMinutesAndSeconds['minutes'])) && (!empty($durationInMinutesAndSeconds['seconds']))) {
            $this->duration = ($durationInMinutesAndSeconds['minutes'] * 60) + $durationInMinutesAndSeconds['seconds'];
        }
    }

    /**
     * Is multistream.
     *
     * @return bool TRUE if multimediaObject has tracks with tags presenter/delivery and presentation/delivery, FALSE otherwise
     */
    public function isMultistream()
    {
        $presenterTracks = $this->getFilteredTracksWithTags(['presenter/delivery']);
        $presentationTracks = $this->getFilteredTracksWithTags(['presentation/delivery']);
        if ($presenterTracks && $presentationTracks) {
            return true;
        }

        return false;
    }

    /**
     * Set textindex.
     *
     * @param array $textindex
     */
    public function setTextIndex($textindex)
    {
        $this->textindex = $textindex;
    }

    /**
     * Get textindex.
     *
     * @return array
     */
    public function getTextIndex()
    {
        return $this->textindex;
    }

    /**
     * Set secondarytextindex.
     *
     * @param array $secondarytextindex
     */
    public function setSecondaryTextIndex($secondarytextindex)
    {
        $this->secondarytextindex = $secondarytextindex;
    }

    /**
     * Get secondarytextindex.
     *
     * @return array
     */
    public function getSecondaryTextIndex()
    {
        return $this->secondarytextindex;
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

    // End of Group section

    /**
     * Update duration.
     */
    private function updateDuration()
    {
        if (0 == count($this->tracks)) {
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
