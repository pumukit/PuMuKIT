<?php

namespace App\Series\Domain\Event;

use Pumukit\SchemaBundle\Document\Series;
use Symfony\Contracts\EventDispatcher\Event;

final class SeriesDeletedEvent extends Event
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
