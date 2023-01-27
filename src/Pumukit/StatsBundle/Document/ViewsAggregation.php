<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\StatsBundle\Document\ViewsAggregation.
 *
 * See ´PumukitAggregateCommand´
 *
 * @MongoDB\Document(repositoryClass="Pumukit\StatsBundle\Repository\ViewsAggregationRepository")
 */
class ViewsAggregation
{
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
     * @MongoDB\Index
     */
    private $date;

    /**
     * @var string
     *
     * @MongoDB\Field(type="object_id")
     * @MongoDB\Index
     */
    private $multimediaObject;

    /**
     * @var string
     *
     * @MongoDB\Field(type="object_id")
     * @MongoDB\Index
     */
    private $series;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int", strategy="increment" )
     */
    private $numView;

    public function __construct($multimediaObject, $series, $numView)
    {
        $this->date = new \DateTime('now');
        $this->multimediaObject = $multimediaObject;
        $this->series = $series;
        $this->numView = $numView;
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
     * Set numView.
     *
     * @param int $numView
     *
     * @return self
     */
    public function setNumViews($numView)
    {
        $this->numView = $numView;

        return $this;
    }

    /**
     * Get numView.
     *
     * @return int $numView
     */
    public function getNumViews()
    {
        return $this->numView;
    }
}
