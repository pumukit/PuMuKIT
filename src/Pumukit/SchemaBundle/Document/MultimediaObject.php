<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Pumukit\SchemaBundle\Document\MultimediaObject.
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\MultimediaObjectRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(name="text_index", keys={"$**"="text"}, options={"language_override"="english"})
 * })
 */
class MultimediaObject
{
    use Traits\Keywords;
    use Traits\Properties;

    const STATUS_PUBLISHED = 0;
    const STATUS_BLOQ = 1; //Kept for backwards compatibility
    const STATUS_BLOCKED = 1;
    const STATUS_HIDE = 2; //Kept for backwards compatibility
    const STATUS_HIDDEN = 2;
    const STATUS_NEW = -1;
    const STATUS_PROTOTYPE = -2;

    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string
     *
     * @MongoDB\String
     * @MongoDB\Index
     */
    private $secret;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Series", inversedBy="multimedia_objects", simple=true)
     * @Gedmo\SortableGroup
     * @MongoDB\Index
     */
    private $series;

    /**
     * NOTE: This field is for MongoDB Search Index purposes.
     *       Do not use this field and do not create setter and/or getter.
     *
     * @var string
     *
     * @MongoDB\Raw
     */
    private $seriesTitle = array('en' => '');

    /**
     * @var Broadcast
     *
     * @deprecated in version 2.3
     * use EmbeddedBroadcast instead
     *
     * @MongoDB\ReferenceOne(targetDocument="Broadcast", inversedBy="multimedia_object", simple=true)
     */
    private $broadcast;

    /**
     * @var EmbeddedBroadcast
     *
     * @MongoDB\EmbedOne(targetDocument="EmbeddedBroadcast")
     */
    private $embeddedBroadcast;

    /**
     * @var ArrayCollection
     *
     * @MongoDB\EmbedMany(targetDocument="EmbeddedTag")
     */
    private $tags;

    /**
     * @var ArrayCollection
     *
     * @MongoDB\EmbedMany(targetDocument="Track")
     */
    private $tracks;

    /**
     * @var ArrayCollection
     *
     * @MongoDB\EmbedMany(targetDocument="Pic")
     */
    private $pics;

    /**
     * @var ArrayCollection
     *
     * @MongoDB\EmbedMany(targetDocument="Material")
     */
    private $materials;

    /**
     * @var ArrayCollection
     *
     * @MongoDB\EmbedMany(targetDocument="Link")
     */
    private $links;

    /**
     * @var ArrayCollection
     *
     * @MongoDB\ReferenceMany(targetDocument="Group", simple=true, sort={"key":1}, strategy="setArray")
     */
    private $groups;

    /**
     * @var int
     *
     * @MongoDB\Int
     * @Gedmo\SortablePosition
     */
    private $rank;

    /**
     * @var int
     *
     * @MongoDB\Int
     */
    private $status = self::STATUS_NEW;

    /**
     * @var date
     *
     * @MongoDB\Date
     * @MongoDB\Index
     */
    private $record_date;

    /**
     * @var date
     *
     * @MongoDB\Date
     * @MongoDB\Index
     */
    private $public_date;

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $title = array('en' => '');

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $subtitle = array('en' => '');

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $description = array('en' => '');

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $line2 = array('en' => '');

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $copyright;

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $license;

    /**
     * @var int
     *
     * @MongoDB\Int
     */
    private $duration = 0;

    /**
     * @var int
     *
     * @MongoDB\Int
     * @MongoDB\Increment
     */
    private $numview = 0;

    /**
     * @var ArrayCollection
     *
     * @MongoDB\EmbedMany(targetDocument="EmbeddedRole")
     */
    private $people;

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var locale
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->secret = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $this->tracks = new ArrayCollection();
        $this->pics = new ArrayCollection();
        $this->materials = new ArrayCollection();
        $this->links = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->people = new ArrayCollection();
        $this->groups = new ArrayCollection();

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @param DateTime $recordDate
     */
    public function setRecordDate($recordDate)
    {
        $this->record_date = $recordDate;
    }

    /**
     * Get record_date.
     *
     * @return datetime
     */
    public function getRecordDate()
    {
        return $this->record_date;
    }

    /**
     * Set public_date.
     *
     * @param DateTime $publicDate
     */
    public function setPublicDate($publicDate)
    {
        $this->public_date = $publicDate;
    }

    /**
     * Get public_date.
     *
     * @return datetime
     */
    public function getPublicDate()
    {
        return $this->public_date;
    }

    /**
     * Set title.
     *
     * @param string      $title
     * @param string|null $locale
     */
    public function setTitle($title, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        $this->title[$locale] = $title;
    }

    /**
     * Get title.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getTitle($locale = null)
    {
        if ($locale == null) {
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
     * @param string|null $locale
     */
    public function setSubtitle($subtitle, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        $this->subtitle[$locale] = $subtitle;
    }

    /**
     * Get subtitle.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getSubtitle($locale = null)
    {
        if ($locale == null) {
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
     * @return array
     */
    public function getI18nSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Set description.
     *
     * @param string      $description
     * @param string|null $locale
     */
    public function setDescription($description, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    /**
     * Get description.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getDescription($locale = null)
    {
        if ($locale == null) {
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
     * Set line2.
     *
     * @param string      $line2
     * @param string|null $locale
     */
    public function setLine2($line2, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        $this->line2[$locale] = $line2;
    }

    /**
     * Get line2.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getLine2($locale = null)
    {
        if ($locale == null) {
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

            if ($min == 0) {
                $aux = $seg."''";
            } else {
                $aux = $min."' ".$seg."''";
            }

            return $aux;
        } else {
            return "0''";
        }
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
        $this->seriesTitle = $series->getI18nTitle();
    }

    /**
     * Get series.
     *
     * @return Series
     */
    public function getSeries()
    {
        return $this->series;
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
        if (($this->broadcast instanceof Broadcast) && ($this->status != self::STATUS_PROTOTYPE)) {
            $this->broadcast->decreaseNumberMultimediaObjects();
        }
        $this->broadcast = $broadcast;
        if ($this->status != self::STATUS_PROTOTYPE) {
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
     * @return Broadcast
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
     * Get embeddedBroadcast.
     *
     * @return EmbeddedBroadcast
     */
    public function getEmbeddedBroadcast()
    {
        return $this->embeddedBroadcast;
    }

    /**
     * Is public embedded broadcast.
     *
     * @return Broadcast
     */
    public function isPublicEmbeddedBroadcast()
    {
        return (bool) (!$this->embeddedBroadcast || EmbeddedBroadcast::TYPE_PUBLIC === $this->embeddedBroadcast->getType());
    }

    // Start tag section. Caution: MultimediaObject tags are Tag objects, not strings.

    /**
     * Get tags.
     *
     * @return array
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
     *
     * The original string tag logic used array_unique to avoid tag duplication.
     *
     * @param Tag|EmbeddedTag $tag
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
     *
     * The original string tag logic used array_search to seek the tag element in array.
     * This function uses doctrine2 arrayCollection contains function instead.
     *
     * @param Tag|EmbeddedTag $tagToRemove
     *
     * @return bool TRUE if this multimedia_object contained the specified tag, FALSE otherwise
     */
    public function removeTag($tagToRemove)
    {
        foreach ($this->tags as $tag) {
            if ($tag->getCod() == $tagToRemove->getCod()) {
                $removed = $this->tags->removeElement($tag);
                $this->tags = new ArrayCollection(array_values($this->tags->toArray()));

                return $removed;
            }
        }

        return false;
    }

    /**
     * Contains tag.
     *
     * The original string tag logic used in_array to check it.
     * This function uses doctrine2 arrayCollection contains function instead.
     *
     * @param Tag|EmbeddedTag $tagToCheck
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
     * @param array $tags
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
     * @param array $tags
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
     * Add pic.
     *
     * @param Pic $pic
     */
    public function addPic(Pic $pic)
    {
        $this->pics->add($pic);
    }

    /**
     * Remove pic.
     *
     * @param Pic $pic
     */
    public function removePic(Pic $pic)
    {
        $this->pics->removeElement($pic);
        $this->pics = new ArrayCollection(array_values($this->pics->toArray()));
    }

    /**
     * Remove pic by id.
     *
     * @param string $picId
     */
    public function removePicById($picId)
    {
        $this->pics = $this->pics->filter(function ($pic) use ($picId) {
            return $pic->getId() !== $picId;
        });
        $this->pics = new ArrayCollection(array_values($this->pics->toArray()));
    }

    /**
     * Up pic by id.
     *
     * @param string $picId
     */
    public function upPicById($picId)
    {
        $this->reorderPicById($picId, true);
    }

    /**
     * Down pic by id.
     *
     * @param string $picId
     */
    public function downPicById($picId)
    {
        $this->reorderPicById($picId, false);
    }

    /**
     * Reorder pic by id.
     *
     * @param string $picId
     * @param bool   $up
     */
    private function reorderPicById($picId, $up = true)
    {
        $snapshot = array_values($this->pics->toArray());
        $this->pics->clear();

        $out = array();
        foreach ($snapshot as $key => $pic) {
            if ($pic->getId() === $picId) {
                $out[($key * 10) + ($up ? -11 : 11)] = $pic;
            } else {
                $out[$key * 10] = $pic;
            }
        }

        ksort($out);
        foreach ($out as $pic) {
            $this->pics->add($pic);
        }
    }

    /**
     * Contains pic.
     *
     * @param Pic $pic
     *
     * @return bool
     */
    public function containsPic(Pic $pic)
    {
        return $this->pics->contains($pic);
    }

    /**
     * Get pics.
     *
     * @return ArrayCollection
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Get first pic, null if none.
     *
     * @return Pic
     */
    public function getPic()
    {
        return $this->pics->get(0);
    }

    /**
     * Get pic by id.
     *
     * @param $picId
     *
     * @return Pic|null
     */
    public function getPicById($picId)
    {
        foreach ($this->pics as $pic) {
            if ($pic->getId() == $picId) {
                return $pic;
            }
        }

        return null;
    }

    /**
     * @deprecated: Use PicService, function getFirstUrlPic($object, $absolute, $hd)
     *
     * Get first pic url
     *
     * @param $default string url returned if series without pics
     *
     * @return string
     */
    public function getFirstUrlPic($default = '')
    {
        $url = $default;
        foreach ($this->pics as $pic) {
            if (null !== $pic->getUrl()) {
                $url = $pic->getUrl();
                break;
            }
        }

        return $url;
    }

    /**
     * Get pics with tag.
     *
     * @param string $tag
     *
     * @return ArrayCollection
     */
    public function getPicsWithTag($tag)
    {
        $r = array();

        foreach ($this->pics as $pic) {
            if ($pic->containsTag($tag)) {
                $r[] = $pic;
            }
        }

        return $r;
    }

    /**
     * Get pic with tag.
     *
     * @param string $tag
     *
     * @return Pic|null
     */
    public function getPicWithTag($tag)
    {
        foreach ($this->pics as $pic) {
            if ($pic->containsTag($tag)) {
                return $pic;
            }
        }

        return null;
    }

    /**
     * Get pics with all tags.
     *
     * @param array $tags
     *
     * @return ArrayCollection
     */
    public function getPicsWithAllTags(array $tags)
    {
        $r = array();

        foreach ($this->pics as $pic) {
            if ($pic->containsAllTags($tags)) {
                $r[] = $pic;
            }
        }

        return $r;
    }

    /**
     * Get pics with all tags.
     *
     * @param array $tags
     *
     * @return Pic|null
     */
    public function getPicWithAllTags(array $tags)
    {
        foreach ($this->pics as $pic) {
            if ($pic->containsAllTags($tags)) {
                return $pic;
            }
        }

        return null;
    }

    /**
     * Get pics with any tag.
     *
     * @param array $tags
     *
     * @return ArrayCollection
     */
    public function getPicsWithAnyTag(array $tags)
    {
        $r = array();

        foreach ($this->pics as $pic) {
            if ($pic->containsAnyTag($tags)) {
                $r[] = $pic;
            }
        }

        return $r;
    }

    /**
     * Get pic with any tag.
     *
     * @param array $tags
     *
     * @return Pic|null
     */
    public function getPicWithAnyTag(array $tags)
    {
        foreach ($this->pics as $pic) {
            if ($pic->containsAnyTag($tags)) {
                return $pic;
            }
        }

        return null;
    }

    /**
     * Get filter pics with tag.
     *
     * @param array $any_tags
     * @param array $all_tags
     * @param array $not_any_tags
     * @param array $not_all_tags
     *
     * @return ArrayCollection
     */
    public function getFilteredPicsWithTags(
                                          array $any_tags = array(),
                                          array $all_tags = array(),
                                          array $not_any_tags = array(),
                                          array $not_all_tags = array())
    {
        $r = array();

        foreach ($this->pics as $pic) {
            if ($any_tags && !$pic->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$pic->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $pic->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $pic->containsAllTags($not_all_tags)) {
                continue;
            }

            $r[] = $pic;
        }

        return $r;
    }

    // End of Pic getter - setter etc methods section

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
        $this->tracks = new ArrayCollection(array_values($this->tracks->toArray()));

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
        $this->tracks = new ArrayCollection(array_values($this->tracks->toArray()));

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
     * Reorder track by id.
     *
     * @param string $trackId
     * @param bool   $up
     */
    private function reorderTrackById($trackId, $up = true)
    {
        $snapshot = array_values($this->tracks->toArray());
        $this->tracks->clear();

        $out = array();
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
     * @return Track|null
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
     * @return ArrayCollection
     */
    public function getTracksWithTag($tag)
    {
        $r = array();

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
     * @return Track|null
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
     * @return ArrayCollection
     */
    public function getTracksWithAllTags(array $tags)
    {
        $r = array();

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
     * @return Track|null
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
     * @return ArrayCollection
     */
    public function getTracksWithAnyTag(array $tags)
    {
        $r = array();

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
     * @return Track|null
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
     * Get master track.
     *
     * @param bool $any to get only tagged tracks
     *
     * @return Track|null
     */
    public function getMaster($any = true)
    {
        $master = $this->getTrackWithTag('master');

        if ($master || !$any) {
            return $master;
        }

        $isAudio = $this->isOnlyAudio();

        foreach ($this->tracks as $track) {
            if (($isAudio && $track->isOnlyAudio()) ||
                (!$isAudio && !$track->isOnlyAudio())) {
                return $track;
            }
        }

        return null;
    }

    /**
     * Get audio/video track with tag display. Get an audio track if the object is an audio.
     *
     * @return Track|null
     */
    public function getDisplayTrack()
    {
        return $this->isOnlyAudio() ?
            $this->getFilteredTrackWithTags(array('display')) :
            $this->getFilteredTrackWithTags(array('display'), array(), array('audio'));
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
     * @return ArrayCollection
     */
    public function getFilteredTracksWithTags(
                                            array $any_tags = array(),
                                            array $all_tags = array(),
                                            array $not_any_tags = array(),
                                            array $not_all_tags = array(),
                                            $all = true)
    {
        $r = array();

        foreach ($this->tracks as $track) {
            // TODO Move 'hide' field to tag 'hidden' in track (see hidden vs display tag)
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
     * @return Track|null
     */
    public function getFilteredTrackWithTags(
                                            array $any_tags = array(),
                                            array $all_tags = array(),
                                            array $not_any_tags = array(),
                                            array $not_all_tags = array(),
                                            $all = true)
    {
        foreach ($this->tracks as $track) {
            // TODO Move 'hide' field to tag 'hidden' in track (see hidden vs display tag)
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

    /**
     * Add material.
     *
     * @param Material $material
     */
    public function addMaterial(Material $material)
    {
        $this->materials->add($material);
    }

    /**
     * Remove material.
     *
     * @param Material $material
     */
    public function removeMaterial(Material $material)
    {
        $this->materials->removeElement($material);
        $this->materials = new ArrayCollection(array_values($this->materials->toArray()));
    }

    /**
     * Remove material by id.
     *
     * @param string $materialId
     */
    public function removeMaterialById($materialId)
    {
        $this->materials = $this->materials->filter(function ($material) use ($materialId) {
            return $material->getId() !== $materialId;
        });
        $this->materials = new ArrayCollection(array_values($this->materials->toArray()));
    }

    /**
     * Up material by id.
     *
     * @param string $materialId
     */
    public function upMaterialById($materialId)
    {
        $this->reorderMaterialById($materialId, true);
    }

    /**
     * Down material by id.
     *
     * @param string $materialId
     */
    public function downMaterialById($materialId)
    {
        $this->reorderMaterialById($materialId, false);
    }

    /**
     * Reorder material by id.
     *
     * @param string $materialId
     * @param bool   $up
     */
    private function reorderMaterialById($materialId, $up = true)
    {
        $snapshot = array_values($this->materials->toArray());
        $this->materials->clear();

        $out = array();
        foreach ($snapshot as $key => $material) {
            if ($material->getId() === $materialId) {
                $out[($key * 10) + ($up ? -11 : 11)] = $material;
            } else {
                $out[$key * 10] = $material;
            }
        }

        ksort($out);
        foreach ($out as $material) {
            $this->materials->add($material);
        }
    }

    /**
     * Contains material.
     *
     * @param Material $material
     *
     * @return bool
     */
    public function containsMaterial(Material $material)
    {
        return $this->materials->contains($material);
    }

    /**
     * Get materials.
     *
     * @return ArrayCollection
     */
    public function getMaterials()
    {
        return $this->materials;
    }

    /**
     * Get material by id.
     *
     * @param $materialId
     *
     * @return Material|null
     */
    public function getMaterialById($materialId)
    {
        foreach ($this->materials as $material) {
            if ($material->getId() == $materialId) {
                return $material;
            }
        }

        return null;
    }

    /**
     * Get materials with tag.
     *
     * @param string $tag
     *
     * @return ArrayCollection
     */
    public function getMaterialsWithTag($tag)
    {
        $r = array();

        foreach ($this->materials as $material) {
            if ($material->containsTag($tag)) {
                $r[] = $material;
            }
        }

        return $r;
    }

    /**
     * Get material with tag.
     *
     * @param string $tag
     *
     * @return Material|null
     */
    public function getMaterialWithTag($tag)
    {
        foreach ($this->materials as $material) {
            if ($material->containsTag($tag)) {
                return $material;
            }
        }

        return null;
    }

    /**
     * Get materials with all tags.
     *
     * @param array $tags
     *
     * @return ArrayCollection
     */
    public function getMaterialsWithAllTags(array $tags)
    {
        $r = array();

        foreach ($this->materials as $material) {
            if ($material->containsAllTags($tags)) {
                $r[] = $material;
            }
        }

        return $r;
    }

    /**
     * Get material with all tags.
     *
     * @param array $tags
     *
     * @return Material|null
     */
    public function getMaterialWithAllTags(array $tags)
    {
        foreach ($this->materials as $material) {
            if ($material->containsAllTags($tags)) {
                return $material;
            }
        }

        return null;
    }

    /**
     * Get materials with any tag.
     *
     * @param array $tags
     *
     * @return ArrayCollection
     */
    public function getMaterialsWithAnyTag(array $tags)
    {
        $r = array();

        foreach ($this->materials as $material) {
            if ($material->containsAnyTag($tags)) {
                $r[] = $material;
            }
        }

        return $r;
    }

    /**
     * Get material with any tag.
     *
     * @param array $tags
     *
     * @return Material|null
     */
    public function getMaterialWithAnyTag(array $tags)
    {
        foreach ($this->materials as $material) {
            if ($material->containsAnyTag($tags)) {
                return $material;
            }
        }

        return null;
    }

    /**
     * Get filtered materials with tags.
     *
     * @param array $any_tags
     * @param array $all_tags
     * @param array $not_any_tags
     * @param array $not_all_tags
     *
     * @return ArrayCollection
     */
    public function getFilteredMaterialsWithTags(
                                               array $any_tags = array(),
                                               array $all_tags = array(),
                                               array $not_any_tags = array(),
                                               array $not_all_tags = array())
    {
        $r = array();

        foreach ($this->materials as $material) {
            if ($any_tags && !$material->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$material->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $material->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $material->containsAllTags($not_all_tags)) {
                continue;
            }

            $r[] = $material;
        }

        return $r;
    }

    // End of Material getter - setter etc methods section

    /**
     * Add link.
     *
     * @param Link $link
     */
    public function addLink(Link $link)
    {
        $this->links->add($link);
    }

    /**
     * Remove link.
     *
     * @param Link $link
     */
    public function removeLink(Link $link)
    {
        $this->links->removeElement($link);
        $this->links = new ArrayCollection(array_values($this->links->toArray()));
    }

    /**
     * Remove link by id.
     *
     * @param string $linkId
     */
    public function removeLinkById($linkId)
    {
        $this->links = $this->links->filter(function ($link) use ($linkId) {
            return $link->getId() !== $linkId;
        });
        $this->links = new ArrayCollection(array_values($this->links->toArray()));
    }

    /**
     * Up link by id.
     *
     * @param string $linkId
     */
    public function upLinkById($linkId)
    {
        $this->reorderLinkById($linkId, true);
    }

    /**
     * Down link by id.
     *
     * @param string $linkId
     */
    public function downLinkById($linkId)
    {
        $this->reorderLinkById($linkId, false);
    }

    /**
     * Reorder link by id.
     *
     * @param string $linkId
     * @param bool   $up
     */
    private function reorderLinkById($linkId, $up = true)
    {
        $snapshot = array_values($this->links->toArray());
        $this->links->clear();

        $out = array();
        foreach ($snapshot as $key => $link) {
            if ($link->getId() === $linkId) {
                $out[($key * 10) + ($up ? -11 : 11)] = $link;
            } else {
                $out[$key * 10] = $link;
            }
        }

        ksort($out);
        foreach ($out as $link) {
            $this->links->add($link);
        }
    }

    /**
     * Contains link.
     *
     * @param Link $link
     *
     * @return bool
     */
    public function containsLink(Link $link)
    {
        return $this->links->contains($link);
    }

    /**
     * Get links.
     *
     * @return ArrayCollection
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Get link by id.
     *
     * @param $linkId
     *
     * @return Link|null
     */
    public function getLinkById($linkId)
    {
        foreach ($this->links as $link) {
            if ($link->getId() == $linkId) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Get links with tag.
     *
     * @param string $tag
     *
     * @return ArrayCollection
     */
    public function getLinksWithTag($tag)
    {
        $r = array();

        foreach ($this->links as $link) {
            if ($link->containsTag($tag)) {
                $r[] = $link;
            }
        }

        return $r;
    }

    /**
     * Get link with tag.
     *
     * @param string $tag
     *
     * @return Link|null
     */
    public function getLinkWithTag($tag)
    {
        foreach ($this->links as $link) {
            if ($link->containsTag($tag)) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Get links with all tags.
     *
     * @param array $tags
     *
     * @return ArrayCollection
     */
    public function getLinksWithAllTags(array $tags)
    {
        $r = array();

        foreach ($this->links as $link) {
            if ($link->containsAllTags($tags)) {
                $r[] = $link;
            }
        }

        return $r;
    }

    /**
     * Get links with all tags.
     *
     * @param array $tags
     *
     * @return Link|null
     */
    public function getLinkWithAllTags(array $tags)
    {
        foreach ($this->links as $link) {
            if ($link->containsAllTags($tags)) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Get links with any tag.
     *
     * @param array $tags
     *
     * @return ArrayCollection
     */
    public function getLinksWithAnyTag(array $tags)
    {
        $r = array();

        foreach ($this->links as $link) {
            if ($link->containsAnyTag($tags)) {
                $r[] = $link;
            }
        }

        return $r;
    }

    /**
     * Get link with any tag.
     *
     * @param array $tags
     *
     * @return Link|null
     */
    public function getLinkWithAnyTag(array $tags)
    {
        foreach ($this->links as $link) {
            if ($link->containsAnyTag($tags)) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Get filtered links with tags.
     *
     * @param array $any_tags
     * @param array $all_tags
     * @param array $not_any_tags
     * @param array $not_all_tags
     *
     * @return ArrayCollection
     */
    public function getFilteredLinksWithTags(
                                           array $any_tags = array(),
                                           array $all_tags = array(),
                                           array $not_any_tags = array(),
                                           array $not_all_tags = array())
    {
        $r = array();

        foreach ($this->links as $link) {
            if ($any_tags && !$link->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$link->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $link->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $link->containsAllTags($not_all_tags)) {
                continue;
            }

            $r[] = $link;
        }

        return $r;
    }

    // End of Link getter - setter etc methods section

    // Start people section.

    /**
     * Get people.
     *
     * @return array
     */
    public function getPeople()
    {
        $aux = array();

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
        $aux = array();

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
        $aux = array();

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
     * @param Person|EmbbededPerson $person
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
     * @param Person|EmbeddedPerson $person
     * @param Role|EmbeddedRole     $role
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
     * @param Person|EmbeddedPerson $person
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
     * @param Person|EmbeddedPerson $person
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
     * @param Role|EmbeddedRole $role
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
        $aux = array();

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
     * @param Person|EmbeddedPerson $person
     * @param Role|EmbeddedRole     $role
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
     * @param Person|EmbeddedPerson $person
     * @param Role|EmbeddedRole     $role
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
            $this->people = new ArrayCollection(array_values($this->people->toArray()));
        }

        return $hasRemoved;
    }

    /**
     * Get person with role.
     *
     * @param Person|EmbeddedPerson $person
     * @param Role|EmbeddedRole     $role
     *
     * @return EmbeddedPerson|bool EmbeddedPerson if found, FALSE otherwise
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
     * @param Person|EmbeddedPerson $person
     * @param Role|EmbeddedRole     $role
     */
    public function upPersonWithRole($person, $role)
    {
        $this->reorderPersonWithRole($person, $role, true);
    }

    /**
     * Down person with role.
     *
     * @param Person|EmbeddedPerson $person
     * @param Role|EmbeddedRole     $role
     */
    public function downPersonWithRole($person, $role)
    {
        $this->reorderPersonWithRole($person, $role, false);
    }

    /**
     * Reorder person with role.
     *
     * @param Person|EmbeddedRole $person
     * @param Role\EmbeddedRole   $role
     * @param bool                $up
     */
    public function reorderPersonWithRole($person, $role, $up = true)
    {
        $people = array_values($this->getPeopleByRole($role, true));
        $this->getEmbeddedRole($role)->getPeople()->clear();

        $out = array();
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
     * @param Role|EmbeddedRole
     *
     * @return EmbeddedRole|bool EmbeddedRole if found, FALSE otherwise
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
        } elseif ($role instanceof Role) {
            $embeddedRole = new EmbeddedRole($role);

            return $embeddedRole;
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
     * Add admin group.
     *
     * @param Group $group
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
        $this->groups = new ArrayCollection(array_values($this->groups->toArray()));
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

    // End of Group section

    /**
     * Update duration.
     */
    private function updateDuration()
    {
        $maxDuration = 0;

        foreach ($this->tracks as $mmTrack) {
            if ($mmTrack->getDuration() > $this->getDuration()) {
                $maxDuration = $mmTrack->getDuration();
            }
        }

        if ($maxDuration !== $this->getDuration()) {
            $this->setDuration($maxDuration);
        }
    }

    /**
     * Is only audio.
     *
     * @return bool TRUE if all tracks in multimedia object are only audio, FALSE otherwise
     */
    public function isOnlyAudio()
    {
        if (0 == count($this->tracks)) {
            return false;
        }

        foreach ($this->tracks as $track) {
            if (!$track->isOnlyAudio()) {
                return false;
            }
        }

        return true;
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
        //if ($seconds < 10 ) $minutes = '0' . $seconds;

        return array('minutes' => $minutes, 'seconds' => $seconds);
    }

    /**
     * Set duration in minutes and seconds.
     *
     * @param array
     */
    public function setDurationInMinutesAndSeconds($durationInMinutesAndSeconds)
    {
        if ((!empty($durationInMinutesAndSeconds['minutes'])) && (!empty($durationInMinutesAndSeconds['seconds']))) {
            $this->duration = ($durationInMinutesAndSeconds['minutes'] * 60) + $durationInMinutesAndSeconds['seconds'];
        }
    }
}
