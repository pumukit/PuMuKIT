<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Utils\FileSystemUtils;
use Pumukit\EncoderBundle\Document\Job;

final class JobUpdater
{
    private DocumentManager $documentManager;
    private LoggerInterface $logger;
    private ProfileValidator $profileValidator;
    private MultimediaObjectPropertyJobService $multimediaObjectPropertyJobService;
    private JobValidator $jobValidator;
    private JobExecutor $jobExecutor;

    public function __construct(
        DocumentManager $documentManager,
        LoggerInterface $logger,
        ProfileValidator $profileValidator,
        JobValidator $jobValidator,
        JobExecutor $jobExecutor,
        MultimediaObjectPropertyJobService $multimediaObjectPropertyJobService
    ) {
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->profileValidator = $profileValidator;
        $this->multimediaObjectPropertyJobService = $multimediaObjectPropertyJobService;
        $this->jobValidator = $jobValidator;
        $this->jobExecutor = $jobExecutor;
    }

    public function pauseJob(Job $job): void
    {
        $this->changeStatus($job, Job::STATUS_WAITING, Job::STATUS_PAUSED);
    }

    public function resumeJob(Job $job): void
    {
        $this->changeStatus($job, Job::STATUS_PAUSED, Job::STATUS_WAITING);
    }

    public function errorJob(Job $job): void
    {
        $this->changeStatus($job, $job->getStatus(), Job::STATUS_ERROR);
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

    public function retryJob(Job $job): bool
    {
        if (Job::STATUS_ERROR !== $job->getStatus()) {
            return false;
        }

        $multimediaObject = $this->jobValidator->ensureMultimediaObjectExists($job);
        $profile = $this->profileValidator->ensureProfileExists($job->getProfile());

        $tempDir = $profile['streamserver']['dir_out'].'/'.$multimediaObject->getSeries()->getId();

        FileSystemUtils::createFolder($tempDir);

        $job->setStatus(Job::STATUS_WAITING);
        $job->setPriority(2);
        $job->setTimeIni(new \DateTime('now'));
        $this->documentManager->flush();

        $this->multimediaObjectPropertyJobService->retryJob($multimediaObject, $job);

        $this->jobExecutor->executeNextJob();

        return true;
    }

    private function changeStatus(Job $job, int $actualStatus, int $newStatus): void
    {
        if ($actualStatus === $job->getStatus()) {
            $job->setStatus($newStatus);
            $this->documentManager->flush();
        }
    }
}
