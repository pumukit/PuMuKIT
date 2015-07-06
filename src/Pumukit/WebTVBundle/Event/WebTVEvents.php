<?php

namespace Pumukit\WebTVBundle\Event;

final class WebTVEvents
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
    const MULTIMEDIAOBJECT_VIEW = 'multimediaobject.view';
}
