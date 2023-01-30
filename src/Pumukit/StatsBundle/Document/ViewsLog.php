<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\StatsBundle\Document\ViewsLog.
 *
 * @MongoDB\Document(repositoryClass="Pumukit\StatsBundle\Repository\ViewsLogRepository")
 */
class ViewsLog
{
    use Traits\Properties;

    /**
     * @var string
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(type="date")
     *
     * @MongoDB\Index
     */
    private $date;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $url;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $ip;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $userAgent;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $referer;

    /**
     * @var string
     *
     * @MongoDB\Field(type="object_id")
     *
     * @MongoDB\Index
     */
    private $multimediaObject;

    /**
     * @var string
     *
     * @MongoDB\Field(type="object_id")
     *
     * @MongoDB\Index
     */
    private $series;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $track;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $user;

    public function __construct($url, $ip, $userAgent, $referer, $multimediaObject, $series, $track, $user = null)
    {
        $this->date = new \DateTime('now');
        $this->url = $url;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->referer = $referer;
        $this->multimediaObject = $multimediaObject;
        $this->series = $series;
        $this->track = $track;
        $this->user = $user;
    }

    /**
     * Get id.
     *
     * @return string $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return self
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime $date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set ip.
     *
     * @param string $ip
     *
     * @return self
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip.
     *
     * @return string $ip
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set userAgent.
     *
     * @param string $userAgent
     *
     * @return self
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Get userAgent.
     *
     * @return string $userAgent
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set referer.
     *
     * @param string $referer
     *
     * @return self
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get referer.
     *
     * @return string $referer
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Set multimediaObject.
     *
     * @param string $multimediaObject
     *
     * @return self
     */
    public function setMultimediaObject($multimediaObject)
    {
        $this->multimediaObject = $multimediaObject;

        return $this;
    }

    /**
     * Get multimediaObject.
     *
     * @return string $multimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * Set series.
     *
     * @param string $series
     *
     * @return self
     */
    public function setSeries($series)
    {
        $this->series = $series;

        return $this;
    }

    /**
     * Get series.
     *
     * @return string $series
     */
    public function getSeries()
    {
        return $this->series;
    }

    /**
     * Set track.
     *
     * @param string $track
     *
     * @return self
     */
    public function setTrack($track)
    {
        $this->track = $track;

        return $this;
    }

    /**
     * Get track.
     *
     * @return string $track
     */
    public function getTrack()
    {
        return $this->track;
    }

    /**
     * Set user.
     *
     * @param string $user
     *
     * @return self
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return string $user
     */
    public function getUser()
    {
        return $this->user;
    }
}
