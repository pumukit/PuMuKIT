<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Update;

use Pumukit\SchemaBundle\Document\Series;

final class UpdateSeriesResponse
{
    public function __construct(
        public Series $series
    ) {}
}
