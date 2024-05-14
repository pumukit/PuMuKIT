<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 */
class Track extends Element
{
    /**
     * @MongoDB\Field(type="string")
     */
    private $language;

    /**
     * @MongoDB\Field(type="string")
     */
    private $originalName;

    /**
     * @MongoDB\Field(type="string")
     */
    private $acodec;

    /**
     * @MongoDB\Field(type="string")
     */
    private $vcodec;

    /**
     * @MongoDB\Field(type="int")
     */
    private $bitrate;

    /**
     * @MongoDB\Field(type="string")
     */
    private $framerate;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $only_audio;

    /**
     * @MongoDB\Field(type="int")
     */
    private $channels;

    /**
     * @MongoDB\Field(type="int")
     */
    private $duration = 0;

    /**
     * @MongoDB\Field(type="int")
     */
    private $width;

    /**
     * @MongoDB\Field(type="int")
     */
    private $height;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $allowDownload = false;

    /**
     * @MongoDB\Field(type="int", strategy="increment" )
     */
    private $numview;

    public function __construct()
    {
        $this->language = \Locale::getDefault();
        parent::__construct();
    }

    public function __toString(): string
    {
        return $this->getUrl() ?: $this->getPath();
    }

    public function setLanguage($language): void
    {
        $this->language = $language;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setAcodec($acodec): void
    {
        $this->acodec = $acodec;
    }

    public function getAcodec()
    {
        return $this->acodec;
    }

    public function setOriginalName($originalName): void
    {
        $this->originalName = $originalName;
    }

    public function getOriginalName()
    {
        return $this->originalName;
    }

    public function setVcodec($vcodec): void
    {
        $this->vcodec = $vcodec;
    }

    public function getVcodec()
    {
        return $this->vcodec;
    }

    public function setBitrate($bitrate): void
    {
        $this->bitrate = $bitrate;
    }

    public function getBitrate()
    {
        return $this->bitrate;
    }

    public function setFramerate(?string $framerate): void
    {
        $this->framerate = $framerate;
    }

    public function getFramerate(): ?string
    {
        return $this->framerate;
    }

    public function getNumFrames(): int
    {
        if (!$this->isOnlyAudio()) {
            return $this->getFrameNumber($this->getDuration());
        }

        return 0;
    }

    public function getFrameNumber($seg): int
    {
        if (false !== strpos($this->getFramerate(), '/')) {
            $aux = explode('/', $this->getFramerate());

            if (0 === (int) $aux[1]) {
                return 0;
            }

            return (int) ($seg * (int) $aux[0] / (int) $aux[1]);
        }

        return (int) ($seg * $this->getFramerate());
    }

    public function getTimeOfAFrame($frame)
    {
        if (!$this->getFramerate()) {
            return 0;
        }

        if (false !== strpos($this->getFramerate(), '/')) {
            $aux = explode('/', $this->getFramerate());

            return (float) ($frame * (int) $aux[1] / (int) $aux[0]);
        }

        return (float) ($frame / $this->getFramerate());
    }

    public function setOnlyAudio($onlyAudio): void
    {
        $this->only_audio = $onlyAudio;
    }

    public function getOnlyAudio()
    {
        return $this->only_audio;
    }

    public function isOnlyAudio()
    {
        return $this->only_audio;
    }

    public function setChannels($channels): void
    {
        $this->channels = $channels;
    }

    public function getChannels()
    {
        return $this->channels;
    }

    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setWidth($width): void
    {
        $this->width = $width;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setHeight($height): void
    {
        $this->height = $height;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setNumview($numview): void
    {
        $this->numview = $numview;
    }

    public function incNumview(): void
    {
        ++$this->numview;
    }

    public function getNumview()
    {
        return $this->numview;
    }

    public function setAllowDownload($allowDownload): void
    {
        $this->allowDownload = $allowDownload;
    }

    public function getAllowDownload(): bool
    {
        return $this->allowDownload;
    }

    public function getResolution(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public function setResolution($resolution): void
    {
        if ((!empty($resolution['width'])) && (!empty($resolution['height']))) {
            $this->width = $resolution['width'];
            $this->height = $resolution['height'];
        }
    }

    public function getAspectRatio()
    {
        return (0 === $this->height || null === $this->height || $this->isOnlyAudio()) ? 0 : $this->width / $this->height;
    }

    public function getDurationInMinutesAndSeconds(): array
    {
        $minutes = floor($this->getDuration() / 60);

        $seconds = $this->getDuration() % 60;

        return ['minutes' => $minutes, 'seconds' => $seconds];
    }

    public function setDurationInMinutesAndSeconds($durationInMinutesAndSeconds): void
    {
        if ((!empty($durationInMinutesAndSeconds['minutes'])) && (!empty($durationInMinutesAndSeconds['seconds']))) {
            $this->duration = ($durationInMinutesAndSeconds['minutes'] * 60) + $durationInMinutesAndSeconds['seconds'];
        }
    }

    public function isMaster(): bool
    {
        return $this->containsTag('master');
    }

    public function getProfileName()
    {
        foreach ($this->getTags() as $tag) {
            if (0 === strpos($tag, 'profile:')) {
                return substr($tag, 8);
            }
        }

        return null;
    }
}
