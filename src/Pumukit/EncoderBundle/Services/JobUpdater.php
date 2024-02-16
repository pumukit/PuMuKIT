<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Document\Job;

final class JobUpdater
{
    private DocumentManager $documentManager;
    private LoggerInterface $logger;

    public function __construct(DocumentManager $documentManager, LoggerInterface $logger)
    {

        $this->documentManager = $documentManager;
        $this->logger = $logger;
    }

    public function pauseJob(Job $job): void
    {
        $this->changeStatus($job, Job::STATUS_WAITING, Job::STATUS_PAUSED);
    }

    public function resumeJob(Job $job): void
    {
        $this->changeStatus($job, Job::STATUS_PAUSED, Job::STATUS_WAITING);
    }

    public function updateJobPriority(Job $job, int $priority): void
    {
        $job->setPriority($priority);
        $this->documentManager->flush();
    }

    public function cancelJob(Job $job): void
    {
        if ((Job::STATUS_WAITING !== $job->getStatus()) && (Job::STATUS_PAUSED !== $job->getStatus())) {
            $message = '[cancelJob] Trying to cancel job "'.$job->getId().'" that is not paused or waiting';
            $this->logger->error($message);

            throw new \Exception($message);
        }
        $this->documentManager->remove($job);
        $this->documentManager->flush();
    }

    private function changeStatus(Job $job, int $actualStatus, int $newStatus): void
    {
        if ($actualStatus === $job->getStatus()) {
            $job->setStatus($newStatus);
            $this->documentManager->flush();
        }
    }
}
