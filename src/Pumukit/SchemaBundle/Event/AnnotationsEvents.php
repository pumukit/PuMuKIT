<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

final class AnnotationsEvents
{
    /**
     * The annotations.update event is thrown each time an
     * annotation is edited (through put).
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\AnnotationsUpdateEvent instance.
     *
     * @var string
     */
    public const UPDATE = 'annotations.update';
    public const GET = 'annotations.get';
}
