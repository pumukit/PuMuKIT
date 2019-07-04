<?php

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\EventDispatcher\Event;

class SeriesEvent extends Event
{
    /**
     * @var Series
     */
    protected $series;

    /**
     * @param Series $series
     */
    public function __construct(Series $series)
    {
        $this->series = $series;
    }

    /**
     * @return Series
     */
    public function getSeries()
    {
        return $this->series;
    }
}
