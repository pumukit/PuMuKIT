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
     * Dispatchs the event MATERIAL_CREATE
     * 'material.create' passing
     * the multimedia object and the material
     *
     * @param MultimediaObject $multimediaObject
     * @param Material         $material
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, Material $material)
    {
        $event = new MaterialEvent($multimediaObject, $material);
        $this->dispatcher->dispatch(SchemaEvents::MATERIAL_CREATE, $event);
    }

    /**
     * Dispatch update.
     *
     * Dispatchs the event MATERIAL_UPDATE
     * 'material.update' passing
     * the multimedia object and the material
     *
     * @param MultimediaObject $multimediaObject
     * @param Material         $material
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, Material $material)
    {
        $event = new MaterialEvent($multimediaObject, $material);
        $this->dispatcher->dispatch(SchemaEvents::MATERIAL_UPDATE, $event);
    }

    /**
     * Dispatch delete.
     *
     * Dispatchs the event MATERIAL_DELETE
     * 'material.delete' passing
     * the multimedia object and the material
     *
     * @param MultimediaObject $multimediaObject
     * @param Material         $material
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, Material $material)
    {
        $event = new MaterialEvent($multimediaObject, $material);
        $this->dispatcher->dispatch(SchemaEvents::MATERIAL_DELETE, $event);
    }
}
