<?php

namespace App\Series\Domain\Event;

use Pumukit\SchemaBundle\Document\Series;

final class SeriesUpdatedEvent
{
    public const NAME = 'series.updated';

    public function __construct(
        private Series $series
    ) {}

    public function getSeries(): Series
    {
        return $this->series;
    }
}
