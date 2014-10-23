<?php

namespace Pumukit\DirectBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pumukit\DirectBundle\Document\Event
 *
 * @MongoDB\Document(repositoryClass="Pumukit\DirectBundle\Repository\EventRepository")
 */
class Event
{
  /** 
   * @var int $id
   * 
   * @MongoDB\Id
   */
  private $id;

  /**
   * @var int $direct_id
   *
   * @MongoDB\ReferenceOne(targetDocument="Direct")
   */
  private $direct_id;

  /**
   * @var string $name
   *
   * @MongoDB\String
   */
  private $name;

  /**
   * @var string $place
   *
   * @MongoDB\String
   */
  private $place;

  /**
   * @var timestamp $date
   *
   * @MongoDB\Timestamp
   */
  private $date;

  /**
   * @var int $duration
   *
   * @MongoDB\Int
   */
  private $duration;

  /**
   * @var boolean $display
   * 
   * @MongoDB\Boolean
   */
  private $display = true;

  /**
   * @var boolean $create_serial
   *
   * @MongoDB\Boolean
   */
  private $create_serial = true;

  /**
   * @var locale $locale
   */
  private $locale = 'en';

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
   * Set place
   *
   * @param string $place
   */
  public function setPlace($place)
  {
    $this->place = $place;
  }
  
  /**
   * Get place
   *
   * @return string
   */
  public function getPlace()
  {
    return $this->place;
  }

  /**
   * Set date
   *
   * @param Timestamp $date
   */
  public function setDate($date)
  {
    $this->date = $date;
  }
  
  /**
   * Get date
   *
   * @return Timestamp
   */
  public function getDate()
  {
    return $this->date;
  }

  /**
   * Set duration
   *
   * @param int $duration
   */
  public function setDuration($duration)
  {
    $this->duration = $duration;
  }
  
  /**
   * Get duration
   *
   * @return int
   */
  public function getDuration()
  {
    return $this->duration;
  }

  /**
   * Set display
   *
   * @param boolean $display
   */
  public function setDisplay($display)
  {
    $this->display = $display;
  }
  
  /**
   * Get display
   *
   * @return boolean
   */
  public function getDisplay()
  {
    return $this->display;
  }

  /**
   * Set create_serial
   *
   * @param string $create_serial
   */
  public function setCreateSerial($create_serial)
  {
    $this->create_serial = $create_serial;
  }
  
  /**
   * Get create_serial
   *
   * @return boolean
   */
  public function getCreateSerial()
  {
    return $this->create_serial;
  }
}