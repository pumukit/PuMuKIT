<?php

namespace Pumukit\EncoderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\EncoderBundle\Document\Job;

abstract class BasePumukitEncoderCommand extends ContainerAwareCommand
{
    protected function formatStatus($job_status)
    {
        $tags = array(
            Job::STATUS_ERROR => array("<error>", "</error>"),
            Job::STATUS_PAUSED => array("", ""),
            Job::STATUS_WAITING => array("<fg=black;bg=cyan>", "</fg=black;bg=cyan>"),
            Job::STATUS_EXECUTING => array("<question>", "</question>"),
            Job::STATUS_FINISHED => array("<info>", "</info>"),
        );

        return $tags[$job_status][0] . Job::$statusTexts[$job_status] . $tags[$job_status][1];
    }
}
