<?php

declare(strict_types=1);

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
    public const PUBLICATION_SUBMIT = 'publication.submit';
}
