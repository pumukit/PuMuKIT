<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Domain\Event;

use Pumukit\EncoderBundle\Document\Job;

final class JobUpdatedEvent
{
    public const NAME = 'job.updated';

    public function __construct(private Job $job) {}

    public function getJob(): Job
    {
        return $this->job;
    }
}
