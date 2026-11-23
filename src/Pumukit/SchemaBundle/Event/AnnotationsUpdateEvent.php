<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class AnnotationsUpdateEvent extends Event
{
    public const EVENT_NAME = 'annotations.update';

    /**
     * @var string
     */
    protected $multimediaObject;

    /**
     * @param string $multimediaObject
     */
    public function __construct($multimediaObject)
    {
        $this->multimediaObject = $multimediaObject;
    }

    /**
     * @return string
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }
}
