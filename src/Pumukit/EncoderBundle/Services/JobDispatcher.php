<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\EncoderEvents;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class JobDispatcher
{
    private EventDispatcherInterface $eventDispatcher;
    private JobValidator $jobValidator;

    public function __construct(EventDispatcherInterface $eventDispatcher, JobValidator $jobValidator)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->jobValidator = $jobValidator;
    }

    public function dispatch($success, Job $job, Track $track = null): void
    {
        $multimediaObject = $this->jobValidator->ensureMultimediaObjectExists($job);

        $event = new JobEvent($job, $track, $multimediaObject);
        $this->eventDispatcher->dispatch($event, $success ? EncoderEvents::JOB_SUCCESS : EncoderEvents::JOB_ERROR);
    }
}
