<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectDurationService
{
    public function getMmobjDuration(MultimediaObject $multimediaObject): ?int
    {
        if (0 === $multimediaObject->getDuration() && $multimediaObject->isExternalType()) {
            return null;
        }

        return $multimediaObject->getDuration();
    }
}
