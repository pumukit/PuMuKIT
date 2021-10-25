<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\LiveRepository")
 */
class Live
{
    public const LIVE_TYPE_WOWZA = 'WOWZA';
    public const LIVE_TYPE_WOWZA_DUAL_STREAM = 'WOWZA_DUAL_STREAM';
    public const LIVE_TYPE_AMS = 'AMS';
    public const LIVE_TYPE_FMS = 'FMS'; //Kept for backwards compatibility
    public const LIVE_TYPE_WMS = 'WMS'; //Kept for backwards compatibility

    protected static $instances = [];

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     * @Assert\Url(protocols= {"rtmpt", "rtmp", "http", "mms", "rtp", "https"})
     */
    private $url;

    /**
     * @MongoDB\Field(type="string")
     */
    private $passwd;

    /**
     * @MongoDB\Field(type="string")
     */
    private $live_type = self::LIVE_TYPE_WOWZA;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $qualities;

    /**
     * @MongoDB\Field(type="string")
     */
    private $ip_source;

    /**
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     */
    private $source_name;

    /**
     * @MongoDB\Field(type="string")
     */
    private $source_name2;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $index_play = false;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $chat = false;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $broadcasting = false;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $debug = false;

    /**
     * @MongoDB\Field(type="raw")
     * @Assert\NotBlank()
     */
    private $name = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    private $locale = 'en';

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUrl($url): void
    {
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setPasswd($passwd): void
    {
        $this->passwd = $passwd;
    }

    public function getPasswd()
    {
        return $this->passwd;
    }

    public function setLiveType($live_type): void
    {
        $this->live_type = $live_type;
    }

    public function getLiveType(): string
    {
        return $this->live_type;
    }

    /**
     * @Assert\IsTrue(message = "Live type not valid")
     */
    public function isValidLiveType(): bool
    {
        return in_array($this->live_type, [
            self::LIVE_TYPE_WOWZA,
            self::LIVE_TYPE_WOWZA_DUAL_STREAM,
            self::LIVE_TYPE_AMS,
            self::LIVE_TYPE_WMS,
            self::LIVE_TYPE_FMS,
        ]);
    }

    public function setQualities($qualities): void
    {
        $this->qualities = $qualities;
    }

    public function getQualities()
    {
        return $this->qualities;
    }

    public function setIpSource($ip_source): void
    {
        $this->ip_source = $ip_source;
    }

    public function getIpSource()
    {
        return $this->ip_source;
    }

    public function setSourceName($source_name): void
    {
        $this->source_name = $source_name;
    }

    public function setSourceName2($source_name): void
    {
        $this->source_name2 = $source_name;
    }

    public function getSourceName()
    {
        return $this->source_name;
    }

    public function getSourceName2()
    {
        return $this->source_name2;
    }

    public function setIndexPlay($index_play): void
    {
        $this->index_play = $index_play;
    }

    public function getIndexPlay(): bool
    {
        return $this->index_play;
    }

    public function isChat(): bool
    {
        return $this->chat;
    }

    public function setChat($chat): void
    {
        $this->chat = $chat;
    }

    public function setBroadcasting($broadcasting): void
    {
        $this->broadcasting = $broadcasting;
    }

    public function getBroadcasting(): bool
    {
        return $this->broadcasting;
    }

    public function setDebug($debug): void
    {
        $this->debug = $debug;
    }

    public function getDebug(): bool
    {
        return $this->debug;
    }

    public function setName($name, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->name[$locale] = $name;
    }

    public function getName($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->name[$locale] ?? '';
    }

    public function setI18nName(array $name): void
    {
        $this->name = $name;
    }

    public function getI18nName(): array
    {
        return $this->name;
    }

    public function setDescription($description, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    public function getDescription($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->description[$locale] ?? '';
    }

    public function setI18nDescription(array $description): void
    {
        $this->description = $description;
    }

    public function getI18nDescription(): array
    {
        return $this->description;
    }

    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function cloneResource(): Live
    {
        $aux = clone $this;
        $aux->id = null;

        return $aux;
    }

    public function getInfo(): string
    {
        return sprintf('%s (%s/%s/%s)', $this->getName(), $this->getUrl(), $this->getSourceName(), $this->getSourceName2());
    }
}
