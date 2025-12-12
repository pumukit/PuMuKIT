<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Create;

use Pumukit\SchemaBundle\Document\Series;

final class CreateSeriesResponse
{
    public function __construct(
        public Series $series
    ) {}
}
