<?php

namespace Pumukit\DirectBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

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
   * @var int $direct
   *
   * @MongoDB\ReferenceOne(targetDocument="Direct")
   */
  private $direct;

  /**
   * @var string $name
   *
   * @MongoDB\Raw
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
   * Set direct
   *
   * @param string $direct
   */
  public function setDirect($direct)
  {
      $this->direct = $direct;
  }

  /**
   * Get direct
   *
   * @return string
   */
  public function getDirect()
  {
      return $this->direct;
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
          return;
      }

      return $this->name[$locale];
  }

  /**
   * Set I18n name
   *
   * @param array $name
   */
  public function setI18nName(array $name)
  {
      $this->name = $name;
  }

  /**
   * Get i18n name
   *
   * @return array
   */
  public function getI18nName()
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
