<?php

namespace App\Series\Application\ViewSeriesMultimediaObjects;

use Pumukit\SchemaBundle\Document\MultimediaObject;

final class ViewSeriesMultimediaObjectsResponse
{
    public function __construct(
        public readonly array $multimediaObjects,
        public readonly int $total
    ) {}
}
