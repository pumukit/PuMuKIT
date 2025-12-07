<?php

namespace App\ContentManagement\Series\Application\Find;

use Pumukit\SchemaBundle\Document\Series;

final class FindSeriesResponse
{
    public function __construct(public readonly Series $series) {}
}
