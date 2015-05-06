<?php

namespace Pumukit\WorkflowBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class WorkflowService
{
    private $dm;
    private $logger;
    private $jobService;

    public function __construct(DocumentManager $documentManager, JobService $jobService, LoggerInterface $logger) 
    {
        $this->dm = $documentManager;
        $this->jobService = $jobService;
        $this->logger = $logger;
    }


    public function onJobSuccess(JobEvent $event)
    {
        $this->checkMultimediaObject($event->getMultimediaObject());
    }

    public function onMultimediaobjectUpdate(MultimediaObjectEvent $event)
    {
        $this->checkMultimediaObject($event->getMultimediaObject());
    }

    /**
     * 
     * TODO Add doc.
     *
     */
    private function checkMultimediaObject(MultimediaObject $multimediaObject)
    {
        $master = $multimediaObject->getTrackWithTag("master");
        $publicTracks = $multimediaObject->getTracksWithTag("display");

        if(($multimediaObject->containsTagWithCod("PUCHWEBTV"))
           && ($master)
           && (!$publicTracks)) {

            //TODO audio??
            //TODO no repeat JOB.
            $targetProfile = $multimediaObject->isOnlyAudio() ? "TODO" : "video_h264";
            $this->logger->info(sprintf("WorkflowService creates new job (%s) for multimedia object %s", $targetProfile, $multimediaObject->getId()));
            $this->jobService->addJob($master->getPath(), $targetProfile, 2, $multimediaObject, $master->getLanguage());
        }

    }
}

