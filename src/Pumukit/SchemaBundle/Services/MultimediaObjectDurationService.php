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

    public function getMmobjDuration(MultimediaObject $mmobj)
    {
        return $mmobj->getDuration();
    }
}
