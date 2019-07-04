<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\Track.
 *
 * @MongoDB\EmbeddedDocument
 */
class Track extends Element
{
    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $language;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $originalName;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $acodec;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $vcodec;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $bitrate;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $framerate;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="boolean")
     */
    private $only_audio;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $channels;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $duration = 0;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $width;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $height;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="boolean")
     */
    private $allowDownload = false;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     * @MongoDB\Increment
     */
    private $numview;

    public function __construct()
    {
        $this->language = \Locale::getDefault();
        parent::__construct();
    }

    /**
     * Set language.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Get language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set acodec.
     *
     * @param string $acodec
     */
    public function setAcodec($acodec)
    {
        $this->acodec = $acodec;
    }

    /**
     * Get acodec.
     *
     * @return string
     */
    public function getAcodec()
    {
        return $this->acodec;
    }

    /**
     * Set originalName.
     *
     * @param string $originalName
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;
    }

    /**
     * Get originalName.
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Set vcodec.
     *
     * @param string $vcodec
     */
    public function setVcodec($vcodec)
    {
        $this->vcodec = $vcodec;
    }

    /**
     * Get vcodec.
     *
     * @return string
     */
    public function getVcodec()
    {
        return $this->vcodec;
    }

    /**
     * Set bitrate.
     *
     * @param int $bitrate
     */
    public function setBitrate($bitrate)
    {
        $this->bitrate = $bitrate;
    }

    /**
     * Get bitrate.
     *
     * @return int
     */
    public function getBitrate()
    {
        return $this->bitrate;
    }

    /**
     * Set framerate.
     *
     * @param string $framerate
     */
    public function setFramerate($framerate)
    {
        $this->framerate = $framerate;
    }

    /**
     * Get framerate.
     *
     * @return string
     */
    public function getFramerate()
    {
        return $this->framerate;
    }

    /**
     * Get total number of frames.
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

            return (int) ($seg * (int) ($aux[0]) / (int) ($aux[1]));
        } else {
            return (int) ($seg * $this->getFramerate());
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

            return (float) ($frame * (int) ($aux[1]) / (int) ($aux[0]));
        } else {
            return (float) ($frame / $this->getFramerate());
        }
    }

    /**
     * Set only_audio.
     *
     * @param bool $onlyAudio
     */
    public function setOnlyAudio($onlyAudio)
    {
        $this->only_audio = $onlyAudio;
    }

    /**
     * Get only_audio.
     *
     * @return bool
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
     * @return bool
     */
    public function isOnlyAudio()
    {
        return $this->only_audio;
    }

    /**
     * Set channels.
     *
     * @param int $channels
     */
    public function setChannels($channels)
    {
        $this->channels = $channels;
    }

    /**
     * Get channels.
     *
     * @return int
     */
    public function getChannels()
    {
        return $this->channels;
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
     * Set width.
     *
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * Get width.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height.
     *
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Get height.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set numview.
     *
     * @param int $numview
     */
    public function setNumview($numview)
    {
        $this->numview = $numview;
    }

    /**
     * Increment numview.
     */
    public function incNumview()
    {
        ++$this->numview;
    }

    /**
     * Get numview.
     *
     * @return int
     */
    public function getNumview()
    {
        return $this->numview;
    }

    /**
     * Set allowDownload.
     *
     * @param bool $allowDownload
     */
    public function setAllowDownload($allowDownload)
    {
        $this->allowDownload = $allowDownload;
    }

    /**
     * Get allowDownload.
     */
    public function getAllowDownload()
    {
        return $this->allowDownload;
    }

    /**
     * Get Resolution.
     *
     * @return array
     */
    public function getResolution()
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * Set Resolution.
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
     * Get video aspect ratio.
     *
     * @return float
     */
    public function getAspectRatio()
    {
        return (0 == $this->height) ? 0 : $this->width / $this->height;
    }

    /**
     * Get duration in minutes and seconds.
     *
     * @return array
     */
    public function getDurationInMinutesAndSeconds()
    {
        $minutes = floor($this->getDuration() / 60);

        $seconds = $this->getDuration() % 60;
        //if ($seconds < 10 ) $minutes = '0' . $seconds;

        return ['minutes' => $minutes, 'seconds' => $seconds];
    }

    /**
     * Set duration in minutes and seconds.
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
     * @return bool
     */
    public function isMaster()
    {
        return $this->containsTag('master');
    }

    /**
     * Return the profiles used to generate the track.
     *
     * @return string|null
     */
    public function getProfileName()
    {
        foreach ($this->getTags() as $tag) {
            if (0 === strpos($tag, 'profile:')) {
                return substr($tag, 8);
            }
        }

        return null;
    }

    /**
     * To string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUrl() ? $this->getUrl() : $this->getPath();
    }
}
