<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\SeriesEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SeriesEventDispatcherService
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch create.
     *
     * Dispatchs the event SERIES_CREATE
     * 'series.create' passing
     * the series
     *
     * @param Series $series
     */
    public function dispatchCreate(Series $series)
    {
        $event = new SeriesEvent($series);
        $this->dispatcher->dispatch(SchemaEvents::SERIES_CREATE, $event);
    }

    /**
     * Dispatch update.
     *
     * Dispatchs the event SERIES_UPDATE
     * 'series.update' passing
     * the series
     *
     * @param Series $series
     */
    public function dispatchUpdate(Series $series)
    {
        $event = new SeriesEvent($series);
        $this->dispatcher->dispatch(SchemaEvents::SERIES_UPDATE, $event);
    }

    /**
     * Dispatch delete.
     *
     * Dispatchs the event SERIES_DELETE
     * 'series.delete' passing
     * the series
     *
     * @param Series $series
     */
    public function dispatchDelete(Series $series)
    {
        $event = new SeriesEvent($series);
        $this->dispatcher->dispatch(SchemaEvents::SERIES_DELETE, $event);
    }
}
