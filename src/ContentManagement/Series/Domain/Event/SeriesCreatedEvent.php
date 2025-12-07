<?php

namespace App\ContentManagement\Series\Domain\Event;

use Pumukit\SchemaBundle\Document\Series;

final class SeriesCreatedEvent
{
    public const NAME = 'series.created';

    public function __construct(
        private Series $series
    ) {}

    public function getSeries(): Series
    {
        return $this->series;
    }
}
