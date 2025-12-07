<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Clone;

use Pumukit\SchemaBundle\Document\Series;

final class CloneSeriesResponse
{
    public function __construct(
        public readonly Series $clonedSeries,
        public readonly int $multimediaObjectsCloned
    ) {}
}
