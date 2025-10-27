<?php

namespace App\Series\Application\ViewSeriesEvents;

final class ViewSeriesEventsResponse
{
    public function __construct(
        public readonly array $multimediaObjects,
        public readonly int $total
    ) {}
}
