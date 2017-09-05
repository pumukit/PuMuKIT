<?php

namespace Pumukit\NewAdminBundle\Event;

final class BackofficeEvents
{
    /**
     * The publication.submit event is thrown each time a
     * new publication channel have configuration panel.
     *
     * The event listener receives an
     * Pumukit\NewAdminBundle\Event\PublicationSubmitEvent instance.
     *
     * @var string
     */
    const PUBLICATION_SUBMIT = 'publication.submit';
}
