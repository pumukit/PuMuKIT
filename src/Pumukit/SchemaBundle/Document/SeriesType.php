<?php

namespace Pumukit\SchemaBundle\Document;

//use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
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
   * @MongoDB\Raw
   */
  private $name = array('en' => '');

  /**
   * @var string $description
   *
   * @MongoDB\Raw
   */
  private $description = array('en' => '');

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
   * Used locale to override Translation listener`s locale
   * this is not a mapped field of entity metadata, just a simple property
   * @var locale $locale
   */
  private $locale = 'en';

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
  public function setName($name, $locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      $this->name[$locale] = $name;
  }

  /**
   * Get name
   *
   * @return string
   */
  public function getName($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->name[$locale])) {
          return null;
      }

      return $this->name[$locale];
  }

  /**
   * Set description
   *
   * @param string $description
   */
  public function setDescription($description, $locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      $this->description[$locale] = $description;
  }

  /**
   * Get description
   *
   * @return string
   */
  public function getDescription($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->description[$locale])) {
          return null;
      }

      return $this->description[$locale];
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
   * Set locale
   *
   * @param string $locale
   */
  public function setLocale($locale)
  {
      $this->locale = $locale;
  }

  /**
   * Get locale
   *
   * @return string
   */
  public function getLocale()
  {
      return $this->locale;
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
