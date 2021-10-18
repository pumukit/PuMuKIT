<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\Series;
use Symfony\Contracts\EventDispatcher\Event;

class SeriesEvent extends Event
{
    /**
     * @var Series
     */
    protected $series;

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
