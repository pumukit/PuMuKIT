<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\MaterialEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MaterialEventDispatcherService
{
    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch the event MATERIAL_CREATE 'material.create' passing the multimedia object and the material.
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, Material $material): void
    {
        $event = new MaterialEvent($multimediaObject, $material);
        $this->dispatcher->dispatch($event, SchemaEvents::MATERIAL_CREATE);
    }

    /**
     * Dispatch the event MATERIAL_UPDATE 'material.update' passing the multimedia object and the material.
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, Material $material): void
    {
        $event = new MaterialEvent($multimediaObject, $material);
        $this->dispatcher->dispatch($event, SchemaEvents::MATERIAL_UPDATE);
    }

    /**
     * Dispatch the event MATERIAL_DELETE 'material.delete' passing the multimedia object and the material.
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, Material $material): void
    {
        $event = new MaterialEvent($multimediaObject, $material);
        $this->dispatcher->dispatch($event, SchemaEvents::MATERIAL_DELETE);
    }
}
