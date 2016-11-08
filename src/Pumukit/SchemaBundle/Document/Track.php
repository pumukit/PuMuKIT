<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\Track
 *
 * @MongoDB\EmbeddedDocument
 */
class Track extends Element
{
    /**
   * @var string $language
   *
   * @MongoDB\String
   */
  private $language;

  /**
   * @var string $acodec
   *
   * @MongoDB\String
   */
  private $acodec;

  /**
   * @var string $vcodec
   *
   * @MongoDB\String
   */
  private $vcodec;

  /**
   * @var int $bitrate
   *
   * @MongoDB\Int
   */
  private $bitrate;

  /**
   * @var string $framerate
   *
   * @MongoDB\String
   */
  private $framerate;

  /**
   * @var boolean $only_audio
   *
   * @MongoDB\Boolean
   */
  private $only_audio;

  /**
   * @var int $channels
   *
   * @MongoDB\Int
   */
  private $channels;

  /**
   * @var int $duration
   *
   * @MongoDB\Int
   */
  private $duration = 0;

  /**
   * @var int $width
   *
   * @MongoDB\Int
   */
  private $width;

  /**
   * @var int $height
   *
   * @MongoDB\Int
   */
  private $height;

  /**
   * @var int $numview
   *
   * @MongoDB\Int
   * @MongoDB\Increment
   */
  private $numview;

    public function __construct()
    {
        $this->language = \Locale::getDefault();
        parent::__construct();
    }

  /**
   * Set language
   *
   * @param string $language
   */
  public function setLanguage($language)
  {
      $this->language = $language;
  }

  /**
   * Get language
   *
   * @return string
   */
  public function getLanguage()
  {
      return $this->language;
  }

  /**
   * Set acodec
   *
   * @param string $acodec
   */
  public function setAcodec($acodec)
  {
      $this->acodec = $acodec;
  }

  /**
   * Get acodec
   *
   * @return string
   */
  public function getAcodec()
  {
      return $this->acodec;
  }

  /**
   * Set vcodec
   *
   * @param string $vcodec
   */
  public function setVcodec($vcodec)
  {
      $this->vcodec = $vcodec;
  }

  /**
   * Get vcodec
   *
   * @return string
   */
  public function getVcodec()
  {
      return $this->vcodec;
  }

  /**
   * Set bitrate
   *
   * @param integer $bitrate
   */
  public function setBitrate($bitrate)
  {
      $this->bitrate = $bitrate;
  }

  /**
   * Get bitrate
   *
   * @return integer
   */
  public function getBitrate()
  {
      return $this->bitrate;
  }

  /**
   * Set framerate
   *
   * @param string $framerate
   */
  public function setFramerate($framerate)
  {
      $this->framerate = $framerate;
  }

  /**
   * Get framerate
   *
   * @return string
   */
  public function getFramerate()
  {
      return $this->framerate;
  }

  /**
   * Get total number of frames
   *
   * @return int
   */
  public function getNumFrames()
  {
      return $this->getFrameNumber($this->getDuration());
  }

  /**
   * Get frame number of a instant in seg.
   *
   * @return int
   */
  public function getFrameNumber($seg)
  {
      if (false !== strpos($this->getFramerate(), '/')) {
          $aux = explode('/', $this->getFramerate());
          return intval($seg * intval($aux[0]) / intval($aux[1]));
      } else {
          return intval($seg * $this->getFramerate());
      }
  }


  /**
   * Get instant in seg of a frame number.
   *
   * @return float
   */
  public function getTimeOfAFrame($frame)
  {
      if (!$this->getFramerate()) {
          return 0;
      }

      if (false !== strpos($this->getFramerate(), '/')) {
          $aux = explode('/', $this->getFramerate());
          return floatval($frame * intval($aux[1]) / intval($aux[0]));
      } else {
          return floatval($frame / $this->getFramerate());
      }
  }


  /**
   * Set only_audio
   *
   * @param boolean $onlyAudio
   */
  public function setOnlyAudio($onlyAudio)
  {
      $this->only_audio = $onlyAudio;
  }

  /**
   * Get only_audio
   *
   * @return boolean
   */
  public function getOnlyAudio()
  {
      return $this->only_audio;
  }

  /**
   * Get only_audio.
   *
   * getOnlyAudio proxy. Same API as MultimediaObject
   *
   * @return boolean
   */
  public function isOnlyAudio()
  {
      return $this->only_audio;
  }

  /**
   * Set channels
   *
   * @param integer $channels
   */
  public function setChannels($channels)
  {
      $this->channels = $channels;
  }

  /**
   * Get channels
   *
   * @return integer
   */
  public function getChannels()
  {
      return $this->channels;
  }

  /**
   * Set duration
   *
   * @param integer $duration
   */
  public function setDuration($duration)
  {
      $this->duration = $duration;
  }

  /**
   * Get duration
   *
   * @return integer
   */
  public function getDuration()
  {
      return $this->duration;
  }

  /**
   * Set width
   *
   * @param integer $width
   */
  public function setWidth($width)
  {
      $this->width = $width;
  }

  /**
   * Get width
   *
   * @return integer
   */
  public function getWidth()
  {
      return $this->width;
  }

  /**
   * Set height
   *
   * @param integer $height
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

  /**
   * Set numview
   *
   * @param integer $numview
   */
  public function setNumview($numview)
  {
      $this->numview = $numview;
  }

  /**
   * Increment numview
   *
   */
  public function incNumview()
  {
      $this->numview++;
  }

  /**
   * Get numview
   *
   * @return integer
   */
  public function getNumview()
  {
      return $this->numview;
  }

  /**
   * Get Resolution
   *
   * @return array
   */
  public function getResolution()
  {
      return array('width' => $this->width,
         'height' => $this->height, );
  }

  /**
   * Set Resolution
   *
   * @param array
   */
  public function setResolution($resolution)
  {
      if ((!empty($resolution['width'])) && (!empty($resolution['height']))) {
          $this->width = $resolution['width'];
          $this->height = $resolution['height'];
      }
  }

  /**
   * Get video aspect ratio
   *
   * @return float
   */
  public function getAspectRatio()
  {
      return (0 == $this->height) ? 0 : $this->width/$this->height;
  }
    
  /**
   * Get duration in minutes and seconds
   *
   * @return array
   */
  public function getDurationInMinutesAndSeconds()
  {
      $minutes = floor($this->getDuration() / 60);

      $seconds = $this->getDuration() % 60;
      //if ($seconds < 10 ) $minutes = '0' . $seconds;

      return array('minutes' => $minutes, 'seconds' => $seconds);
  }

  /**
   * Set duration in minutes and seconds
   *
   * @param array
   */
  public function setDurationInMinutesAndSeconds($durationInMinutesAndSeconds)
  {
      if ((!empty($durationInMinutesAndSeconds['minutes'])) && (!empty($durationInMinutesAndSeconds['seconds']))) {
          $this->duration = ($durationInMinutesAndSeconds['minutes'] * 60) + $durationInMinutesAndSeconds['seconds'];
      }
  }

  /**
   * Return true if track is a master.
   *
   * @return boolean
   */
  public function isMaster()
  {
      return $this->containsTag("master");
  }
}
