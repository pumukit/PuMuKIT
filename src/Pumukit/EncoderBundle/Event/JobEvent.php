<?php

namespace Pumukit\EncoderBundle\Event;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\EventDispatcher\Event;

class JobEvent extends Event
{
    /**
     * @var Job
     */
    protected $job;

    /**
     * @var Track
     */
    protected $track;

    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @param Job              $job
     * @param Track            $track
     * @param MultimediaObject $multimediaObject
     */
    public function __construct(Job $job, Track $track = null, MultimediaObject $multimediaObject = null)
    {
        $this->job = $job;
        $this->track = $track;
        $this->multimediaObject = $multimediaObject;
    }

    /**
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @return Track
     */
    public function getTrack()
    {
        return $this->track;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }
}
