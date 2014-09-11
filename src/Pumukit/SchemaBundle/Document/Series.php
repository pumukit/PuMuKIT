<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\SeriesRepository")
 */
class Series
{

	/**
	 * @MongoDB\Id
	 */
	protected $id;

	/**
	 * @MongoDB\ReferenceOne(targetDocument="SeriesType", inversedBy="id")
	 */
	private $series_type;

	/**
	 * @var ArrayCollection $multimedia_objects
	 *
	 * @MongoDB\ReferenceMany(targetDocument="MultimediaObject", mappedBy="series")
	 */
	private $multimedia_objects;

	/**
	 * @var datetime $public_date
	 *
	 * @MongoDB\Date
	 */
	private $public_date;

	/**
	 * @var string $title
	 *
	 * //@Gedmo\Translatable
	 * @MongoDB\String
	 */
	private $title;

	/**
	 * @var string $subtitle
	 *
	 * //@Gedmo\Translatable
	 * @MongoDB\String
	 */
	private $subtitle;

	/**
	 * @var text $description
	 *
	 * //@Gedmo\Translatable
	 * @MongoDB\String
	 */
	private $description;

	/**
	 * @var text $header
	 *
	 * //@Gedmo\Translatable
	 * @MongoDB\String
	 */
	private $header;

	/**
	 * @var text $footer
	 *
	 * //@Gedmo\Translatable
	 * @MongoDB\String
	 */
	private $footer;

	/**
	 * @var string $copyright
	 *
	 * //@Gedmo\Translatable
	 * @MongoDB\String
	 */
	private $copyright;

	/**
	 * @var string $keyword
	 *
	 * //@Gedmo\Translatable
	 * @MongoDB\String
	 */
	private $keyword;

	/**
	 * @var string $line2
	 *
	 * //@Gedmo\Translatable
	 * @MongoDB\String
	 */
	private $line2;

	/**
	 * //@Gedmo\Locale
	 * Used locale to override Translation listener`s locale
	 * this is not a mapped field of entity metadata, just a simple property
	 * @var locale $locale
	 */
	private $locale;

	public function __construct()
	{
		$this->multimedia_objects = new ArrayCollection();
	}

	/**
	 * Get id
	 *
	 * @return id
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set series_type
	 *
	 * @param SeriesType $series_type
	 */
	public function setSeriesType(SeriesType $series_type)
	{
		$this->series_type = $series_type;
	}

	/**
	 * Get series_type
	 *
	 * @return SeriesType
	 */
	public function getSeriesType()
	{
		return $this->series_type;
	}

	/**
	 * Add multimedia_object
	 *
	 * @param MultimediaObject $multimedia_object
	 */
	public function addMultimediaObject(MultimediaObject $multimedia_object)
	{
		$this->multimedia_objects[] = $multimedia_object;
		$multimedia_object->setSeries($this);

		$multimedia_object->setRank(count($this->multimedia_objects));
	}

	/**
	 * Remove multimedia_object
	 *
	 * @param MultimediaObject $multimedia_object
	 */
	public function removeMultimediaObject(MultimediaObject $multimedia_object)
	{
		$this->multimedia_objects->removeElement($multimedia_object);
	}

	/**
	 * Contains multimedia_object
	 *
	 * @param MultimediaObject $multimedia_object
	 *
	 * @return boolean
	 */
	public function containsMultimediaObject(MultimediaObject $multimedia_object)
	{
		return $this->multimedia_objects->contains($multimedia_object);
	}

	/**
	 * Get multimedia_objects
	 *
	 * @return ArrayCollection
	 */
	public function getMultimediaObjects()
	{
		return $this->multimedia_objects;
	}

	/**
	 * Set public_date
	 *
	 * @param datetime $publicDate
	 */
	public function setPublicDate($publicDate)
	{
		$this->public_date = $publicDate;
	}

	/**
	 * Get public_date
	 *
	 * @return datetime
	 */
	public function getPublicDate()
	{
		return $this->public_date;
	}

	/**
	 * Set title
	 *
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set subtitle
	 *
	 * @param string $subtitle
	 */
	public function setSubtitle($subtitle)
	{
		$this->subtitle = $subtitle;
	}

	/**
	 * Get subtitle
	 *
	 * @return string
	 */
	public function getSubtitle()
	{
		return $this->subtitle;
	}

	/**
	 * Set description
	 *
	 * @param text $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * Get description
	 *
	 * @return text
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set header
	 *
	 * @param text $header
	 */
	public function setHeader($header)
	{
		$this->header = $header;
	}

	/**
	 * Get header
	 *
	 * @return text
	 */
	public function getHeader()
	{
		return $this->header;
	}

	/**
	 * Set footer
	 *
	 * @param text $footer
	 */
	public function setFooter($footer)
	{
		$this->footer = $footer;
	}

	/**
	 * Get footer
	 *
	 * @return text
	 */
	public function getFooter()
	{
		return $this->footer;
	}

	/**
	 * Set copyright
	 *
	 * @param string $copyright
	 */
	public function setCopyright($copyright)
	{
		$this->copyright = $copyright;
	}

	/**
	 * Get copyright
	 *
	 * @return string
	 */
	public function getCopyright()
	{
		return $this->copyright;
	}

	/**
	 * Set keyword
	 *
	 * @param string $keyword
	 */
	public function setKeyword($keyword)
	{
		$this->keyword = $keyword;
	}

	/**
	 * Get keyword
	 *
	 * @return string
	 */
	public function getKeyword()
	{
		return $this->keyword;
	}

	/**
	 * Set line2
	 *
	 * @param string $line2
	 */
	public function setLine2($line2)
	{
		$this->line2 = $line2;
	}

	/**
	 * Get line2
	 *
	 * @return string
	 */
	public function getLine2()
	{
		return $this->line2;
	}

	public function __toString()
	{
		return $this->getTitle();
	}

	/**
	 * Contains multimediaobject with tags
	 *
	 * @param Tag $tag
	 * @return boolean
	 */
	public function containsMultimediaObjectWithTag(Tag $tag)
	{
		foreach ($this->multimedia_objects as $mmo) {
			if ($mmo->containsTag($tag)) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Get multimediaobjects with a tag
	 *
	 * @param Tag $tag
	 * @return ArrayCollection
	 */
	public function getMultimediaObjectsByTag(Tag $tag)
	{
		$r = array();

		foreach ($this->multimedia_objects as $mmo) {
			if ($mmo->containsTag($tag)) {
				$r[] = $mmo;
			}
		}

		return $r;
	}

	/**
	 * Get one multimedia object with tag
	 *
	 * @param Tag $tag
	 * @return MultimediaObject
	 */
	public function getMultimediaObjectByTag(Tag $tag)
	{
		foreach ($this->multimedia_objects as $mmo) {
			//if ($mmo->tags->contains($tag)) {
			//FIXME no pasa el test phpunit cuando se llama desde seriestest
			if ($mmo->containsTag($tag)) {
				return $mmo;
			}
		}

		return null;
	}

	/**
	 * Get multimediaobjects with all tags
	 *
	 * @param array $tags
	 * @return ArrayCollection
	 */
	public function getMultimediaObjectsWithAllTags(array $tags)
	{
		$r = array();
		foreach ($this->multimedia_objects as $mmo) {
			if ($mmo->containsAllTags($tags)) {
				$r[] = $mmo;
			}
		}

		return $r;
	}

	/**
	 * Get multimediaobject with all tags
	 *
	 * @param array $tags
	 * @return multimedia_object
	 */
	public function getMultimediaObjectWithAllTags(array $tags)
	{
		foreach ($this->multimedia_objects as $mmo) {
			if ($mmo->containsAllTags($tags)) {
				return $mmo;
			}
		}

		return null;
	}

	/**
	 * Get multimediaobjects with any tag
	 *
	 * @param array $tags
	 * @return ArrayCollection
	 */
	public function getMultimediaObjectsWithAnyTag(array $tags)
	{
		$r = array();

		foreach ($this->multimedia_objects as $mmo) {
			if ($mmo->containsAnyTag($tags)) {
				$r[] = $mmo;
			}
		}

		return $r;
	}

	/**
	 * Get multimediaobject with any tag
	 *
	 * @param array $tags
	 * @return MultimediaObject
	 */
	public function getMultimediaObjectWithAnyTag(array $tags)
	{
		foreach ($this->multimedia_objects as $mmo) {
			if ($mmo->containsAnyTag($tags)) {
				return $mmo;
			}
		}

		return null;
	}

	/**
	 * Get tracks ...
	 *
	 * @param array $any_tags
	 * @param array $all_tags
	 * @param array $not_any_tags
	 * @param array $not_all_tags
	 * @return ArrayCollection
	 */
	public function getFilteredMultimediaObjectsByTags(
			array $any_tags = array(),
			array $all_tags = array(),
			array $not_any_tags = array(),
			array $not_all_tags = array())
	{
		$r = array();

		foreach ($this->multimedia_objects as $mmo) {
			if($any_tags && !$mmo->containsAnyTag($any_tags))
				continue;
			if($all_tags && !$mmo->containsAllTags($all_tags))
				continue;
			if($not_any_tags && $mmo->containsAnyTag($not_any_tags))
				continue;
			if($not_all_tags && $mmo->containsAllTags($not_all_tags))
				continue;

			$r[] = $mmo;
		}

		return $r;
	}
}
