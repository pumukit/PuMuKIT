<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Application\Find;

use Pumukit\EncoderBundle\Document\Job;

final class FindJobResponse
{
    public function __construct(public readonly Job $job) {}
}
