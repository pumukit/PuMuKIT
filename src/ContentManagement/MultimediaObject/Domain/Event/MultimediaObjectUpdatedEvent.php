<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Domain\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;

final class MultimediaObjectUpdatedEvent
{
    public function __construct(
        public readonly MultimediaObject $multimediaObject
    ) {}
}
