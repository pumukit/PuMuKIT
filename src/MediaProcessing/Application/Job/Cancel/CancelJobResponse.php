<?php

declare(strict_types=1);

namespace App\MediaProcessing\Application\Job\Cancel;

use Pumukit\EncoderBundle\Document\Job;

final class CancelJobResponse
{
    public function __construct(public readonly Job $job) {}
}
