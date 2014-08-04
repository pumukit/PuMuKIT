<?php

namespace Pumukit\InspectionBundle\Service;

use Pumukit\SchemaBundle\Entity\Track;

interface InspectionServiceInterface
{
    /**
     * Gets file duration in s.
     * @param $file
     * @return integer $duration file duration in s rounded up.
     */
    public function getDuration($file);

    /**
     * Completes track information from a given path.
     * @param Track $track
     */
    public function autocompleteTrack(Track $track);
}
