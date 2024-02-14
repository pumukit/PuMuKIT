<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Event;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Contracts\EventDispatcher\Event;

class JobEvent extends Event
{
    protected $job;
    protected $track;
    protected $multimediaObject;

    public function __construct(Job $job, Track $track = null, MultimediaObject $multimediaObject = null)
    {
        $this->job = $job;
        $this->track = $track;
        $this->multimediaObject = $multimediaObject;
    }

    public function getJob(): Job
    {
        return $this->job;
    }

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function getMultimediaObject(): ?MultimediaObject
    {
        return $this->multimediaObject;
    }
}
