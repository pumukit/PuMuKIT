<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Event;

final class BasePlayerEvents
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
