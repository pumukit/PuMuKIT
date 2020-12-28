<?php

declare(strict_types=1);

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

    public function __construct(MultimediaObject $multimediaObject, Track $track = null)
    {
        $this->multimediaObject = $multimediaObject;
        $this->track = $track;
    }

    /**
     * @return Track|null
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
