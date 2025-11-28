<?php

namespace App\Series\Domain\Event;

use Pumukit\SchemaBundle\Document\Series;

final class SeriesDeletedEvent
{
    public const NAME = 'series.deleted';

    public function __construct(
        private Series $series
    ) {}

    public function getSeries(): Series
    {
        return $this->series;
    }
}
