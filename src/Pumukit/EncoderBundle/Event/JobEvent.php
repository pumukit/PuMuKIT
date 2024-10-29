<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Event;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Contracts\EventDispatcher\Event;

class JobEvent extends Event
{
    protected $job;
    protected $media;
    protected $multimediaObject;

    public function __construct(Job $job, MediaInterface $media = null, MultimediaObject $multimediaObject = null)
    {
        $this->job = $job;
        $this->media = $media;
        $this->multimediaObject = $multimediaObject;
    }

    public function getJob(): Job
    {
        return $this->job;
    }

    public function getMedia(): ?MediaInterface
    {
        return $this->media;
    }

    public function getMultimediaObject(): ?MultimediaObject
    {
        return $this->multimediaObject;
    }
}
