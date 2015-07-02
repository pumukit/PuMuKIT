<?php

namespace Pumukit\SchemaBundle\Event;

final class SchemaEvents
{
    /**
     * The multimediaobject.update event is thrown each time a 
     * multimedia object is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\MultimediaObjectEvent instance.
     *
     * @var string
     */
    const MULTIMEDIAOBJECT_UPDATE = 'multimediaobject.update';
}