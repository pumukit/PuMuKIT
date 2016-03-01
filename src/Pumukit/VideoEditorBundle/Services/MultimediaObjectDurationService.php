<?php

namespace Pumukit\VideoEditorBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectDurationService
{
    private $dm;
    private $repo;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

    public function getMmobjDuration(MultimediaObject $mmobj)
    {
        if($duration = $mmobj->getProperty('soft-editing-duration'))
            return $duration;
        else
            return $mmobj->getDuration();
    }
}
