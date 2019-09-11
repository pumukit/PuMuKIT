<?php

namespace Pumukit\InspectionBundle\Services;

use Pumukit\SchemaBundle\Document\Track;

interface InspectionServiceInterface
{
    /**
     * Gets file duration in s.
     */
    public function getDuration(string $file): int;

    /**
     * Completes track information from a given path.
     * The information is: mime_type, bitrate, duration, size, acodec, vcodec, framerate, channels, width, height.
     */
    public function autocompleteTrack(Track $track): Track;
}
