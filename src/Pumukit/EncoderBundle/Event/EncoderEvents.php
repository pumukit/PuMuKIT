<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Event;

final class EncoderEvents
{
    /**
     * The job.success event is thrown each time a job is finished successfully in the system.
     *
     * The event listener receives a Pumukit\EncoderBundle\Event\JobEvent instance.
     */
    public const JOB_SUCCESS = 'job.success';

    /**
     * The job.success event is thrown each time a job fails in the system.
     *
     * The event listener receives a Pumukit\EncoderBundle\Event\JobEvent instance.
     */
    public const JOB_ERROR = 'job.error';
}
