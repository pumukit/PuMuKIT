<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\EncoderEvents;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class JobDispatcher
{
    private DocumentManager $documentManager;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;

    public function __construct(
        DocumentManager $documentManager,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    )
    {
        $this->documentManager = $documentManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function dispatch($success, Job $job, Track $track = null): void
    {
        $multimediaObject = $this->getMultimediaObject($job);

        $event = new JobEvent($job, $track, $multimediaObject);
        $this->eventDispatcher->dispatch($event, $success ? EncoderEvents::JOB_SUCCESS : EncoderEvents::JOB_ERROR);
    }

    private function getMultimediaObject(Job $job): MultimediaObject
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => $job->getMmId()
        ]);

        if (!$multimediaObject) {
            $errorMsg = sprintf(
                '[createTrackWithJob] Multimedia object %s not found when the job %s creates the track',
                $job->getMmId(),
                $job->getId()
            );
            $this->logger->error($errorMsg);

            throw new \Exception($errorMsg);
        }

        return $multimediaObject;
    }
}
