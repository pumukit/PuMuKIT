<?php

namespace Pumukit\LiveBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Pumukit\SchemaBundle\Document\Pic;

/**
 * Pumukit\LiveBundle\Document\Event.
 *
 * @MongoDB\Document(repositoryClass="Pumukit\LiveBundle\Repository\EventRepository")
 */
class Event
{
    /**
   * @var string
   *
   * @MongoDB\Id
   */
  private $id;

  /**
   * @var Live
   *
   * @MongoDB\ReferenceOne(targetDocument="Live")
   */
  private $live;

  /**
   * @var string
   *
   * @MongoDB\String
   */
  private $name;

  /**
   * @var string
   *
   * @MongoDB\Raw
   */
  private $description = array('en' => '');

  /**
   * @var string
   *
   * @MongoDB\String
   */
  private $place;

  /**
   * @var datetime
   *
   * @MongoDB\Date
   */
  private $date;

  /**
   * @var int
   *
   * @MongoDB\Int
   */
  private $duration = 60;

  /**
   * @var bool
   *
   * @MongoDB\Boolean
   */
  private $display = true;

  /**
   * @var bool
   *
   * @MongoDB\Boolean
   */
  private $create_serial = true;

  /**
   * @var Pic
   *
   * @MongoDB\EmbedOne(targetDocument="Pumukit\SchemaBundle\Document\Pic")
   */
  private $pic;

  /**
   * @var locale
   */
  private $locale = 'en';

  /**
   * Constructor.
   */
  public function __construct()
  {
      $this->date = new \Datetime('now');
  }

  /**
   * Get id.
   *
   * @return int
   */
  public function getId()
  {
      return $this->id;
  }

  /**
   * Set live.
   *
   * @param string $live
   */
  public function setLive($live)
  {
      $this->live = $live;
  }

  /**
   * Get live.
   *
   * @return string
   */
  public function getLive()
  {
      return $this->live;
  }

  /**
   * Set name.
   *
   * @param string $name
   */
  public function setName($name)
  {
      $this->name = $name;
  }

  /**
   * Get name.
   *
   * @return string
   */
  public function getName()
  {
      return $this->name;
  }

  /**
   * Set description.
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
   * Get description.
   *
   * @return string
   */
  public function getDescription($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->description[$locale])) {
          return '';
      }

      return $this->description[$locale];
  }

  /**
   * Set I18n description.
   *
   * @param array $description
   */
  public function setI18nDescription(array $description)
  {
      $this->description = $description;
  }

  /**
   * Get I18n description.
   *
   * @return array
   */
  public function getI18nDescription()
  {
      return $this->description;
  }

  /**
   * Set place.
   *
   * @param string $place
   */
  public function setPlace($place)
  {
      $this->place = $place;
  }

  /**
   * Get place.
   *
   * @return string
   */
  public function getPlace()
  {
      return $this->place;
  }

  /**
   * Set date.
   *
   * @param Date $date
   */
  public function setDate($date)
  {
      $this->date = $date;
  }

  /**
   * Get date.
   *
   * @return Date
   */
  public function getDate()
  {
      return $this->date;
  }

  /**
   * Set duration.
   *
   * @param int $duration
   */
  public function setDuration($duration)
  {
      $this->duration = $duration;
  }

  /**
   * Get duration.
   *
   * @return int
   */
  public function getDuration()
  {
      return $this->duration;
  }

  /**
   * Set display.
   *
   * @param bool $display
   */
  public function setDisplay($display)
  {
      $this->display = $display;
  }

  /**
   * Get display.
   *
   * @return bool
   */
  public function getDisplay()
  {
      return $this->display;
  }

  /**
   * Set create_serial.
   *
   * @param string $create_serial
   */
  public function setCreateSerial($create_serial)
  {
      $this->create_serial = $create_serial;
  }

  /**
   * Get create_serial.
   *
   * @return bool
   */
  public function getCreateSerial()
  {
      return $this->create_serial;
  }

  /**
   * Set locale.
   *
   * @param string $locale
   */
  public function setLocale($locale)
  {
      $this->locale = $locale;
  }

  /**
   * Get locale.
   *
   * @return string
   */
  public function getLocale()
  {
      return $this->locale;
  }

  /**
   * Set Schedule.
   *
   * @return array
   */
  public function getSchedule()
  {
      return array('date' => $this->date,
         'duration' => $this->duration, );
  }

  /**
   * Get Schedule.
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

    /**
     * Set pic.
     *
     * @param Pic $pic
     */
    public function setPic(Pic $pic)
    {
        $this->pic = $pic;
    }

    /**
     * Remove pic.
     *
     * @param Pic $pic
     */
    public function removePic()
    {
        $this->pic = null;
    }

    /**
     * Get pic.
     *
     * @return Pic
     */
    public function getPic()
    {
        return $this->pic;
    }
}
