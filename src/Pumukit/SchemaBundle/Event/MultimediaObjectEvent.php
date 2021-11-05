<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Contracts\EventDispatcher\Event;

class MultimediaObjectEvent extends Event
{
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    public function __construct(MultimediaObject $multimediaObject)
    {
        $this->multimediaObject = $multimediaObject;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }
}
