<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Event\FileEvents;
use Pumukit\CoreBundle\Event\FileRemovedEvent;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\TrackService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class JobRemover
{
    private DocumentManager $documentManager;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private TrackService $trackService;
    private string $tmpPath;
    private string $inboxPath;
    private bool $deleteInboxFiles;

    public function __construct(
        DocumentManager $documentManager,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        TrackService $trackService,
        string $tmpPath,
        string $inboxPath,
        bool $deleteInboxFiles
    ) {
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->trackService = $trackService;
        $this->tmpPath = $tmpPath;
        $this->inboxPath = $inboxPath;
        $this->deleteInboxFiles = $deleteInboxFiles;
    }

    public function deleteTempFilesFromJob(Job $job): void
    {
        if (Job::STATUS_FINISHED !== $job->getStatus()) {
            $message = sprintf(
                __FUNCTION__.' Trying to delete tempFiles from not finished jobs. Job id: %s',
                $job->getId()
            );
            $this->logger->error($message);

            throw new \Exception('Cannot delete temp files from not finished jobs.');
        }

        if (str_starts_with($job->getPathIni(), $this->tmpPath)) {
            unlink($job->getPathIni());
        } elseif ($this->deleteInboxFiles && str_starts_with($job->getPathIni(), $this->inboxPath)) {
            unlink($job->getPathIni());

            $event = new FileRemovedEvent($job->getPathIni());
            $this->eventDispatcher->dispatch($event, FileEvents::FILE_REMOVED);
        }
    }

    public function delete(Job $job): void
    {
        if (Job::STATUS_EXECUTING === $job->getStatus()) {
            $message = sprintf(
                __FUNCTION__.' Trying to delete job "%s" that has executing status. Given status is %s',
                $job->getId(),
                $job->getStatus()
            );
            $this->logger->error($message);

            throw new \Exception($message);
        }

        $this->documentManager->remove($job);
        $this->documentManager->flush();
    }

    public function removeMedia(MultimediaObject $multimediaObject, string $trackId): MultimediaObject
    {
        return $this->trackService->removeTrackFromMultimediaObject($multimediaObject, $trackId);
    }
}
