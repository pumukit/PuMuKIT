<?php

namespace App\UI\Backoffice\MediaProcessing\Job\Helpers;

use Pumukit\EncoderBundle\Document\Job;

final class StatusIcon
{
    public static function convert(int $status): string
    {
        return match ($status) {
            Job::STATUS_ERROR => '<i class="fas fa-times-circle text-danger"></i>',
            Job::STATUS_EXECUTING => '<i class="fas fa-spinner fa-spin text-primary"></i>',
            Job::STATUS_FINISHED => '<i class="fas fa-check-circle text-success"></i>',
            Job::STATUS_PAUSED => '<i class="fas fa-pause-circle text-warning"></i>',
            Job::STATUS_WAITING => '<i class="fas fa-clock text-info"></i>',
        };
    }
}
