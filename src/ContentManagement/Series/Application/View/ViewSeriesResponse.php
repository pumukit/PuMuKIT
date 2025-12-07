<?php

namespace App\ContentManagement\Series\Application\View;

use Pumukit\SchemaBundle\Document\Series;

final class ViewSeriesResponse
{
    public function __construct(
        public readonly Series $series,
        public readonly array $multimediaObjects = [],
        public readonly array $owners = []
    ) {}
}
