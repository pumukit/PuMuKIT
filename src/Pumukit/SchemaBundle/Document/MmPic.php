<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\MmPic
 *
 * @MongoDB\EmbeddedDocument
 */
class MmPic extends MmElement
{
  /**
   * @var int $width
   *
   * @MongoDB\Int
   */
  private $width;

  /**
   * @var integer $height
   *
   * @MongoDB\Int
   */
  private $height;
 
  /**
   * Set width
   *
   * @param int $width
   */
  public function setWidth($width)
  {
      $this->width = $width;
  }

  /**
   * Get width
   *
   * @return int
   */
  public function getWidth()
  {
      return $this->width;
  }

  /**
   * Set height
   *
   * @param int $height
   */
  public function setHeight($height)
  {
      $this->height = $height;
  }

  /**
   * Get height
   *
   * @return integer
   */
  public function getHeight()
  {
      return $this->height;
  }
}
