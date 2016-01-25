<?php

namespace Pumukit\SchemaBundle\Event;

final class AnnotationsAPIEvents
{
    /**
     * The multimediaobject.view event is thrown each time a 
     * multimedia object is played in the webtv portal.
     *
     * The event listener receives an
     * Pumukit\WebTVBundle\Event\ViewedEvent instance.
     *
     * @var string
     */
    const API_UPDATE = 'annotationsapi.update';
    const API_GET = 'annotationsapi.get';

}
