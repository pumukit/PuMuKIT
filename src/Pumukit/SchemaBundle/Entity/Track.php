<?php

namespace Pumukit\SchemaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pumukit\SchemaBundle\Entity\Track
 *
 * @ORM\Table(name="track")
 * @ORM\Entity(repositoryClass="Pumukit\SchemaBundle\Entity\TrackRepository")
 */
class Track extends Element
{
    /**
     * @var string $language
     *
     * @ORM\Column(name="language", type="string", length=2)
     */
    private $language;

    /**
     * @var string $acodec
     *
     * @ORM\Column(name="acodec", type="string", length=100, nullable=true)
     */
    private $acodec;

    /**
     * @var string $vcodec
     *
     * @ORM\Column(name="vcodec", type="string", length=100, nullable=true)
     */
    private $vcodec;

    /**
     * @var integer $bitrate
     *
     * @ORM\Column(name="bitrate", type="integer")
     */
    private $bitrate;

    /**
     * @var integer $framerate
     *
     * @ORM\Column(name="framerate", type="integer", nullable=true)
     */
    private $framerate;

    /**
     * @var boolean $only_audio
     *
     * @ORM\Column(name="only_audio", type="boolean")
     */
    private $only_audio;

    /**
     * @var integer $channels
     *
     * @ORM\Column(name="channels", type="integer", nullable=true)
     */
    private $channels;

    /**
     * @var integer $duration
     *
     * @ORM\Column(name="duration", type="integer")
     */
    private $duration = 0;

    /**
     * @var integer $width
     *
     * @ORM\Column(name="width", type="integer", nullable=true)
     */
    private $width;

    /**
     * @var integer $height
     *
     * @ORM\Column(name="height", type="integer", nullable=true)
     */
    private $height;

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
     * @param integer $framerate
     */
    public function setFramerate($framerate)
    {
        $this->framerate = $framerate;
    }

    /**
     * Get framerate
     *
     * @return integer
     */
    public function getFramerate()
    {
        return $this->framerate;
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
}
