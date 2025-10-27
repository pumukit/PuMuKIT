<?php

namespace App\Series\Application\ViewSeriesEvents;

use Pumukit\SchemaBundle\Document\MultimediaObject;

final class ViewSeriesEventsResponse
{
    public function __construct(
        public readonly array $multimediaObjects,
        public readonly int $total
    ) {}
}
