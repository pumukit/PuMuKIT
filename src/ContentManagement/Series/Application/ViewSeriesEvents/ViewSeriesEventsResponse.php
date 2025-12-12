<?php

namespace App\ContentManagement\Series\Application\ViewSeriesEvents;

final class ViewSeriesEventsResponse
{
    public function __construct(
        public array $multimediaObjects,
        public int $total
    ) {}
}
