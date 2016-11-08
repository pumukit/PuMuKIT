<?php

namespace Pumukit\SchemaBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;

class TrackEvent extends Event
{
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @var Track
     */
    protected $track;

    /**
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     */
    public function __construct(MultimediaObject $multimediaObject, Track $track)
    {
        $this->multimediaObject = $multimediaObject;
        $this->track = $track;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * @return Track
     */
    public function getTrack()
    {
        return $this->track;
    }
}
