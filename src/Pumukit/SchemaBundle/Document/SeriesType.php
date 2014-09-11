<?php

namespace Pumukit\SchemaBundle\Document;

//use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Pumukit\SchemaBundle\Document\Series;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pumukit\SchemaBundle\Document\SeriesType
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\SeriesTypeRepository")
 */
class SeriesType
{
	/**
	 * @var int $id
	 *
	 * @MongoDB\Int
	 * @MongoDB\Id
	 */
	private $id;

	/**
	 * @var string $name
	 *
	 * //@Gedmo\Translatable
	 * @MongoDB\String
	 */
	private $name;

	/**
	 * @var string $description
	 *
	 * //@Gedmo\Translatable
	 * @MongoDB\String
	 */
	private $description;

	/**
	 * @var string $cod
	 *
	 * @MongoDB\String
	 */
	private $cod = 0;

	/**
	 * @var ArrayCollection $series
	 *
	 * @MongoDB\ReferenceMany(targetDocument="Series", mappedBy="series_type")
	 */
	private $series;

	/**
	 * //@Gedmo\Locale
	 * Used locale to override Translation listener`s locale
	 * this is not a mapped field of entity metadata, just a simple property
	 * @var locale $locale
	 */
	private $locale;

	public function __construct()
	{
		$this->series = new ArrayCollection();
	}

	/**
	 * Get id
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set description
	 *
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * Get description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set cod
	 *
	 * @param string $cod
	 */
	public function setCod($cod)
	{
		$this->cod = $cod;
	}

	/**
	 * Get cod
	 *
	 * @return string
	 */
	public function getCod()
	{
		return $this->cod;
	}

	/**
	 * Add series
	 *
	 * @param Series $series
	 */
	public function addSeries(Series $series)
	{
		$this->series[] = $series;
		// $this->series = array_unique($this->tags);
		// Extra verification
		$series->setSeriesType($this);
	}

	/**
	 * Remove series
	 *
	 * @param Series $series
	 */
	public function removeSeries(Series $series)
	{
		$this->series->removeElement($series);
	}

	/**
	 * Contains series
	 *
	 * @param Series $series
	 *
	 * @return boolean
	 */
	public function containsSeries(Series $series)
	{
		return $this->series->contains($series);
	}

	/**
	 * Get series
	 *
	 * @return ArrayCollection
	 */
	public function getSeries()
	{
		return $this->series;
	}

	/**
	 * Add series
	 *
	 * @param Series $series
	 * @return SeriesType
	 */
	public function addSerie(Series $series)
	{
		$this->series[] = $series;

		return $this;
	}

	/**
	 * Remove series
	 *
	 * @param Series $series
	 */
	public function removeSerie(Series $series)
	{
		$this->series->removeElement($series);
	}
}
