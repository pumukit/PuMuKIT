<?php

namespace Pumukit\LiveBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\LiveBundle\Document\Event
 *
 * @MongoDB\Document(repositoryClass="Pumukit\LiveBundle\Repository\EventRepository")
 */
class Event
{
    /**
   * @var string $id
   *
   * @MongoDB\Id
   */
  private $id;

  /**
   * @var Live $live
   *
   * @MongoDB\ReferenceOne(targetDocument="Live")
   */
  private $live;

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
   * @var datetime $date
   *
   * @MongoDB\Date
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
   * Set live
   *
   * @param string $live
   */
  public function setLive($live)
  {
      $this->live = $live;
  }

  /**
   * Get live
   *
   * @return string
   */
  public function getLive()
  {
      return $this->live;
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
   * @param Date $date
   */
  public function setDate($date)
  {
      $this->date = $date;
  }

  /**
   * Get date
   *
   * @return Date
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
   * Set Schedule
   *
   * @return array
   */
  public function getSchedule()
  {
      return array('date' => $this->date,
         'duration' => $this->duration, );
  }

  /**
   * Get Schedule
   *
   * @param array
   */
  public function setSchedule($schedule)
  {
      if ((!empty($schedule['date'])) && (!empty($schedule['duration']))) {
          $this->date = $schedule['date'];
          $this->duration = $schedule['duration'];
      }
  }
}
