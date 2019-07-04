<?php

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\EventDispatcher\Event;

class MaterialEvent extends Event
{
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @var Material
     */
    protected $material;

    /**
     * @param MultimediaObject $multimediaObject
     * @param Material         $material
     */
    public function __construct(MultimediaObject $multimediaObject, Material $material)
    {
        $this->multimediaObject = $multimediaObject;
        $this->material = $material;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * @return Material
     */
    public function getMaterial()
    {
        return $this->material;
    }
}
