<?php

namespace Pumukit\SchemaBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;

class PicEvent extends Event
{
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @var Pic
     */
    protected $pic;

    /**
     * @param MultimediaObject $multimediaObject
     * @param Pic              $pic
     */
    public function __construct(MultimediaObject $multimediaObject, Pic $pic)
    {
        $this->multimediaObject = $multimediaObject;
        $this->pic = $pic;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * @return Pic
     */
    public function getPic()
    {
        return $this->pic;
    }
}
