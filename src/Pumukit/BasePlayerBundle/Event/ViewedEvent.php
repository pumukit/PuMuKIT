<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Event;

use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Contracts\EventDispatcher\Event;

class ViewedEvent extends Event
{
    protected ?MediaInterface $media;
    protected MultimediaObject $multimediaObject;

    public function __construct(MultimediaObject $multimediaObject, ?MediaInterface $media = null)
    {
        $this->multimediaObject = $multimediaObject;
        $this->media = $media;
    }

    public function getMedia(): ?MediaInterface
    {
        return $this->media;
    }

    /** Deprecated. Use getMedia instead. */
    public function getTrack(): ?MediaInterface
    {
        return $this->getMedia();
    }

    public function getMultimediaObject(): MultimediaObject
    {
        return $this->multimediaObject;
    }
}
