<?php

namespace Pumukit\LiveBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pumukit\LiveBundle\Document\Live.
 *
 * @MongoDB\Document(repositoryClass="Pumukit\LiveBundle\Repository\LiveRepository")
 */
class Live
{
    const LIVE_TYPE_WOWZA = 'WOWZA';
    const LIVE_TYPE_AMS = 'AMS';
    const LIVE_TYPE_FMS = 'FMS'; //Kept for backwards compatibility
    const LIVE_TYPE_WMS = 'WMS'; //Kept for backwards compatibility

    /**
     * Constructor.
     */
    protected static $instances = [];

    /**
     * @var int
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     * @Assert\Url(protocols= {"rtmpt", "rtmp", "http", "mms", "rtp", "https"})
     */
    private $url;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $passwd;

    /**
     * @var int
     * @MongoDB\Field(type="string")
     */
    private $live_type = self::LIVE_TYPE_WOWZA;

    /**
     * @var int
     * @MongoDB\Field(type="int")
     */
    private $width = 720;

    /**
     * @var int
     * @MongoDB\Field(type="int")
     */
    private $height = 576;

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $qualities;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $ip_source;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     */
    private $source_name;

    /**
     * @var bool
     * @MongoDB\Field(type="boolean")
     */
    private $index_play = false;

    /**
     * @var bool
     * @MongoDB\Field(type="boolean")
     */
    private $chat = false;

    /**
     * @var bool
     * @MongoDB\Field(type="boolean")
     */
    private $broadcasting = false;

    /**
     * @var bool
     * @MongoDB\Field(type="boolean")
     */
    private $debug = false;

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     * @Assert\NotBlank()
     */
    private $name = ['en' => ''];

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @var string
     */
    private $locale = 'en';

    public function __toString()
    {
        return $this->getName();
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
     * Set url.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set passwd.
     *
     * @param string $passwd
     */
    public function setPasswd($passwd)
    {
        $this->passwd = $passwd;
    }

    /**
     * Get passwd.
     *
     * @return string
     */
    public function getPasswd()
    {
        return $this->passwd;
    }

    /**
     * Set live_type.
     *
     * @param string $live_type
     */
    public function setLiveType($live_type)
    {
        $this->live_type = $live_type;
    }

    /**
     * Get live_type.
     *
     * @return string
     */
    public function getLiveType()
    {
        return $this->live_type;
    }

    /**
     * @Assert\IsTrue(message = "Live type not valid")
     */
    public function isValidLiveType()
    {
        return in_array($this->live_type, [
            self::LIVE_TYPE_WOWZA,
            self::LIVE_TYPE_AMS,
            self::LIVE_TYPE_WMS,
            self::LIVE_TYPE_FMS,
        ]);
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
     * Set qualities.
     *
     * @param string $qualities
     */
    public function setQualities($qualities)
    {
        $this->qualities = $qualities;
    }

    /**
     * Get qualities.
     *
     * @return string
     */
    public function getQualities()
    {
        return $this->qualities;
    }

    /**
     * Set ip_source.
     *
     * @param string $ip_source
     */
    public function setIpSource($ip_source)
    {
        $this->ip_source = $ip_source;
    }

    /**
     * Get ip_source.
     *
     * @return string
     */
    public function getIpSource()
    {
        return $this->ip_source;
    }

    /**
     * Set source_name.
     *
     * @param string $source_name
     */
    public function setSourceName($source_name)
    {
        $this->source_name = $source_name;
    }

    /**
     * Get source_name.
     *
     * @return string
     */
    public function getSourceName()
    {
        return $this->source_name;
    }

    /**
     * Set index_play.
     *
     * @param bool $index_play
     */
    public function setIndexPlay($index_play)
    {
        $this->index_play = $index_play;
    }

    /**
     * Get index_play.
     *
     * @return bool
     */
    public function getIndexPlay()
    {
        return $this->index_play;
    }

    /**
     * @return bool
     */
    public function isChat()
    {
        return $this->chat;
    }

    /**
     * @param bool $chat
     */
    public function setChat($chat)
    {
        $this->chat = $chat;
    }

    /**
     * Set broadcasting.
     *
     * @param bool $broadcasting
     */
    public function setBroadcasting($broadcasting)
    {
        $this->broadcasting = $broadcasting;
    }

    /**
     * Get broadcasting.
     *
     * @return bool
     */
    public function getBroadcasting()
    {
        return $this->broadcasting;
    }

    /**
     * Set debug.
     *
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Get debug.
     *
     * @return bool
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Set name.
     *
     * @param             $name
     * @param null|string $locale
     */
    public function setName($name, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->name[$locale] = $name;
    }

    /**
     * Get name.
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getName($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->name[$locale])) {
            return '';
        }

        return $this->name[$locale];
    }

    /**
     * Set I18n name.
     *
     * @param array $name
     */
    public function setI18nName(array $name)
    {
        $this->name = $name;
    }

    /**
     * Get i18n name.
     *
     * @return string
     */
    public function getI18nName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string      $description
     * @param null|string $locale
     */
    public function setDescription($description, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    /**
     * Get description.
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getDescription($locale = null)
    {
        if (null === $locale) {
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
     * @return string
     */
    public function getI18nDescription()
    {
        return $this->description;
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
     * Clone Live.
     *
     * @return Live
     */
    public function cloneResource()
    {
        $aux = clone $this;
        $aux->id = null;

        return $aux;
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
     * @param mixed $resolution
     */
    public function setResolution($resolution)
    {
        if ((!empty($resolution['width'])) && (!empty($resolution['height']))) {
            $this->width = $resolution['width'];
            $this->height = $resolution['height'];
        }
    }

    /**
     * Return short info about the live channel to use as choice label.
     *
     * @return string
     */
    public function getInfo()
    {
        return sprintf('%s (%s/%s)', $this->getName(), $this->getUrl(), $this->getSourceName());
    }
}
