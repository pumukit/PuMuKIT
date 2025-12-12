<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Pumukit\EncoderBundle\Document\Job;

final readonly class JobCancelledEvent extends DomainEvent
{
    public function __construct(
        public Job $job
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'job.cancelled';
    }
}
