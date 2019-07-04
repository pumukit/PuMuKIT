<?php

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\EventDispatcher\Event;

class AnnotationsUpdateEvent extends Event
{
    const EVENT_NAME = 'annotations.update';
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @param MultimediaObject $multimediaObject
     */
    public function __construct($multimediaObject)
    {
        $this->multimediaObject = $multimediaObject;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }
}
