<?php

namespace Pumukit\SchemaBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Pumukit\SchemaBundle\Document\Series;

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
