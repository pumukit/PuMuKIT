<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\SeriesElement
 *
 * @MongoDB\MappedSuperclass
 */
class SeriesElement extends Element
{
  /**
   * @MongoDB\EmbedOne(targetDocument="Series")
   */
  private $series;
  
  /**
   * Set series
   *
   * @param Series $series
   */
  public function setSeries(Series $series)
  {
    $this->series = $series;
  }
  
  /**
   * Get series
   *
   * @return Series
   */
  public function getSeries()
  {
    return $this->series;
  }

}