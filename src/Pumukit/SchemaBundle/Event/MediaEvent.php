<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Contracts\EventDispatcher\Event;

class MediaEvent extends Event
{
    protected $multimediaObject;

    protected $media;

    public function __construct(MultimediaObject $multimediaObject, MediaInterface $media)
    {
        $this->multimediaObject = $multimediaObject;
        $this->media = $media;
    }

    public function getMultimediaObject(): MultimediaObject
    {
        return $this->multimediaObject;
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }
}
