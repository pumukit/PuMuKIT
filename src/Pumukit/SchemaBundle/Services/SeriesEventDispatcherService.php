<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\SeriesEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SeriesEventDispatcherService
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch the event SERIES_CREATE 'series.create' passing the series.
     */
    public function dispatchCreate(Series $series): void
    {
        $event = new SeriesEvent($series);
        $this->dispatcher->dispatch($event, SchemaEvents::SERIES_CREATE);
    }

    /**
     * Dispatch the event SERIES_UPDATE 'series.update' passing the series.
     */
    public function dispatchUpdate(Series $series): void
    {
        $event = new SeriesEvent($series);
        $this->dispatcher->dispatch($event, SchemaEvents::SERIES_UPDATE);
    }

    /**
     * Dispatch the event SERIES_DELETE 'series.delete' passing the series.
     */
    public function dispatchDelete(Series $series): void
    {
        $event = new SeriesEvent($series);
        $this->dispatcher->dispatch($event, SchemaEvents::SERIES_DELETE);
    }
}
