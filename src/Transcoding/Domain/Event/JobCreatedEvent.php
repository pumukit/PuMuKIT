<?php

declare(strict_types=1);

namespace App\Transcoding\Domain\Event;

use Pumukit\EncoderBundle\Document\Job;

final class JobCreatedEvent
{
    public const NAME = 'job.created';

    public function __construct(private Job $job) {}

    public function getJob(): Job
    {
        return $this->job;
    }
}
