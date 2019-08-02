<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectDurationService
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    /**
     * Get video duration, null if duration is unknow (externalplayer).
     */
    public function getMmobjDuration(MultimediaObject $mmobj): ?int
    {
        if (0 === $mmobj->getDuration() && $mmobj->getProperty('externalplayer')) {
            return null;
        }

        return $mmobj->getDuration();
    }
}
