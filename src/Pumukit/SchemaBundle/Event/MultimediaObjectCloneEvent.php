<?php

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\EventDispatcher\Event;

class MultimediaObjectCloneEvent extends Event
{
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;
    protected $multimediaObjectCloned;

    /**
     * @param MultimediaObject $multimediaObject
     */
    public function __construct(MultimediaObject $multimediaObject, MultimediaObject $multimediaObjectCloned)
    {
        $this->multimediaObject = $multimediaObject;
        $this->multimediaObjectCloned = $multimediaObjectCloned;
    }

    /**
     * @return array
     */
    public function getMultimediaObjects()
    {
        return ['origin' => $this->multimediaObject, 'clon' => $this->multimediaObjectCloned];
    }
}
