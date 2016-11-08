<?php

namespace Pumukit\InspectionBundle\Services;

use Pumukit\SchemaBundle\Document\Track;

interface InspectionServiceInterface
{
    /**
     * Gets file duration in s.
     *
     * @param $file
     *
     * @return int $duration file duration in s rounded up
     */
    public function getDuration($file);

    /**
     * Completes track information from a given path.
     *
     * The information is: mimetype, bitrate, duration, size, acodec, vcodec,
     * framerate, channels, width, height
     *
     * @param Track $track
     */
    public function autocompleteTrack(Track $track);
}
