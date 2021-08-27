<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\EventDispatcher\Event;

class AnnotationsUpdateEvent extends Event
{
    public const EVENT_NAME = 'annotations.update';
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
