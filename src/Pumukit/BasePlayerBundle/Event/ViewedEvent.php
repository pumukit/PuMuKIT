<?php

namespace Pumukit\BasePlayerBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\EventDispatcher\Event;

class ViewedEvent extends Event
{
    /**
     * @var Track
     */
    protected $track;

    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @param Track            $track
     * @param MultimediaObject $multimediaObject
     */
    public function __construct(MultimediaObject $multimediaObject, Track $track = null)
    {
        $this->multimediaObject = $multimediaObject;
        $this->track = $track;
    }

    /**
     * @return Track
     */
    public function getTrack()
    {
        return $this->track;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }
}
