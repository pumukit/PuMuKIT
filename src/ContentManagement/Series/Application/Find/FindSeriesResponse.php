<?php

namespace App\ContentManagement\Series\Application\Find;

use Pumukit\SchemaBundle\Document\Series;

final class FindSeriesResponse
{
    public function __construct(public Series $series) {}
}
