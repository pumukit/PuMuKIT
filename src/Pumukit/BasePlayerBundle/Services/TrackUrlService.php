<?php

namespace Pumukit\BasePlayerBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Track;

class TrackUrlService
{
    private $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function generateTrackFileUrl(Track $track, $absolute = false)
    {
        return $track->getUrl($absolute);
    }
}
