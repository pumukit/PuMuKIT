<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Comments
 *
 * @MongoDB\EmbeddedDocument
 */
class Comments
{
    /**
   * @var int
   *
   * @MongoDB\Id
   */
  private $id;

  /**
   * @var \Date
   *
   * @MongoDB\Date
   */
  private $date;

  /**
   * @var string
   *
   * @MongoDB\String
   */
  private $text;

  /**
   * @var int
   *
   * @MongoDB\Int
   * @MongoDB\EmbedOne(targetDocument="MultimediaObject")
   */
  private $multimedia_object_id;

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
   * Set date
   *
   * @param @MongoDB\Date $date
   * @return Comments
   */
  public function setDate($date)
  {
      $this->date = $date;

      return $this;
  }

  /**
   * Get date
   *
   * @return @MongoDB\Date
   */
  public function getDate()
  {
      return $this->date;
  }

  /**
   * Set text
   *
   * @param string $text
   * @return Comments
   */
  public function setText($text)
  {
      $this->text = $text;

      return $this;
  }

  /**
   * Get text
   *
   * @return string
   */
  public function getText()
  {
      return $this->text;
  }

  /**
   * Set multimedia_object_id
   *
   * @param int $multimediaObjectId
   * @return Comments
   */
  public function setMultimediaObjectId($multimediaObjectId)
  {
      $this->multimedia_object_id = $multimediaObjectId;

      return $this;
  }

  /**
   * Get multimedia_object_id
   *
   * @return int
   */
  public function getMultimediaObjectId()
  {
      return $this->multimedia_object_id;
  }
}
