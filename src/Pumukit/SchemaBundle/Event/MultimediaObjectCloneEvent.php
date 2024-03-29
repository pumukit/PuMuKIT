<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Contracts\EventDispatcher\Event;

class MultimediaObjectCloneEvent extends Event
{
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;
    protected $multimediaObjectCloned;

    public function __construct(MultimediaObject $multimediaObject, MultimediaObject $multimediaObjectCloned)
    {
        $this->multimediaObject = $multimediaObject;
        $this->multimediaObjectCloned = $multimediaObjectCloned;
    }

    /**
     * @return array
     */
    public function getMultimediaObjects()
    {
        return ['origin' => $this->multimediaObject, 'clon' => $this->multimediaObjectCloned];
    }
}
