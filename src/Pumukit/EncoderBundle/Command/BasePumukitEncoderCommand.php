<?php

namespace Pumukit\EncoderBundle\Command;

use Pumukit\EncoderBundle\Document\Job;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class BasePumukitEncoderCommand extends ContainerAwareCommand
{
    protected function formatStatus($job_status)
    {
        $tags = [
            Job::STATUS_ERROR => ['<error>', '</error>'],
            Job::STATUS_PAUSED => ['', ''],
            Job::STATUS_WAITING => ['<fg=black;bg=cyan>', '</fg=black;bg=cyan>'],
            Job::STATUS_EXECUTING => ['<question>', '</question>'],
            Job::STATUS_FINISHED => ['<info>', '</info>'],
        ];

        return $tags[$job_status][0].Job::$statusTexts[$job_status].$tags[$job_status][1];
    }
}
