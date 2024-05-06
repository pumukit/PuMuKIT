<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\EncoderEvents;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
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

    public function dispatch(string $success, Job $job, MediaInterface $media = null): void
    {
        $multimediaObject = $this->jobValidator->ensureMultimediaObjectExists($job);

        $event = new JobEvent($job, $media, $multimediaObject);
        $this->eventDispatcher->dispatch($event, $success ? EncoderEvents::JOB_SUCCESS : EncoderEvents::JOB_ERROR);
    }
}
