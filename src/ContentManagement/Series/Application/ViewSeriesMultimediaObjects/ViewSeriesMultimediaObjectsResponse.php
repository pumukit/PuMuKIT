<?php

namespace App\ContentManagement\Series\Application\ViewSeriesMultimediaObjects;

final class ViewSeriesMultimediaObjectsResponse
{
    public function __construct(
        public array $multimediaObjects,
        public int $total
    ) {}
}
