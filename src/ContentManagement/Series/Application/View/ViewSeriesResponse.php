<?php

namespace App\ContentManagement\Series\Application\View;

use Pumukit\SchemaBundle\Document\Series;

final class ViewSeriesResponse
{
    public function __construct(
        public Series $series,
        public array $multimediaObjects = [],
        public array $owners = []
    ) {}
}
