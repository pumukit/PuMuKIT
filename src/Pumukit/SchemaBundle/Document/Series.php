<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Series
{

	/**
	 * @MongoDB\Id
	 */
	protected $id;

	/**
	 * @var datetime $public_date
	 *
	 * @MongoDB\Date
	 */
	private $public_date;

	/**
	 * @var string $title
	 *
	 * @MongoDB\String
	 */
	private $title;

	/**
	 * @var string $subtitle
	 *
	 * @MongoDB\String
	 */
	private $subtitle;

	/**
	 * @var text $description
	 *
	 * @MongoDB\String
	 */
	private $description;

	/**
	 * @var ArrayCollection $multimedia_objects
	 *
	 * @MongoDB\ReferenceMany(targetDocument="MultimediaObject", mappedBy="series")
	 */
	private $multimedia_objects;


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

	public function __construct()
	{
		$this->multimedia_objects = new \Doctrine\Common\Collections\ArrayCollection();
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
}
