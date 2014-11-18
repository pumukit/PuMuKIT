<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\MmElement
 *
 * @MongoDB\MappedSuperclass
 */
class MmElement extends Element
{
  /**
   * @MongoDB\EmbedOne(targetDocument="MultimediaObject")
   */
  private $multimedia_object;

  /**
   * Set multimedia_object
   *
   * @param MultimediaObject $multimedia_object
   */
  public function setMultimediaObject(MultimediaObject $multimedia_object)
  {
      $this->multimedia_object = $multimedia_object;
  }

  /**
   * Get multimedia_object
   *
   * @return MultimediaObject
   */
  public function getMultimediaObject()
  {
      return $this->multimedia_object;
  }

}