<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectDurationService
{
    public function getMmobjDuration(MultimediaObject $multimediaObject): ?int
    {
        if (0 === $multimediaObject->getDuration() && $multimediaObject->getProperty('externalplayer')) {
            return null;
        }

        return $multimediaObject->getDuration();
    }
}
