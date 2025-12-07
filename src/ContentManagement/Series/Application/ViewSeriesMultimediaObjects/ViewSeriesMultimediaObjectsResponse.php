<?php

namespace App\ContentManagement\Series\Application\ViewSeriesMultimediaObjects;

final class ViewSeriesMultimediaObjectsResponse
{
    public function __construct(
        public readonly array $multimediaObjects,
        public readonly int $total
    ) {}
}
