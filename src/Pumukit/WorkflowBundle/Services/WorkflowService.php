<?php

namespace Pumukit\WorkflowBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class WorkflowService
{
    private $dm;
    private $logger;
    private $jobService;
    private $profileService;

    public function __construct(DocumentManager $documentManager, JobService $jobService, ProfileService $profileService, LoggerInterface $logger) 
    {
        $this->dm = $documentManager;
        $this->jobService = $jobService;
        $this->logger = $logger;
        $this->profiles = $profileService->getProfiles();
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

        $repository = $this->dm->getRepository('PumukitSchemaBundle:Tag');
        $tag = $repository->findOneByCod("PUBCHANNELS");
        if(!$tag) return;

        foreach($tag->getChildren() as $pubchannel) {
            if(($multimediaObject->containsTag($pubchannel))
               && ($master)
               && (!$publicTracks)) {

                foreach($this->profiles as $targetProfile => $profile) {
                    if((in_array($pubchannel->getCod(), array_filter(preg_split('/[,\s]+/', $profile['target']))))
                       && ($multimediaObject->isOnlyAudio() == $profile['audio'])) {

                        $this->logger->info(sprintf("WorkflowService creates new job (%s) for multimedia object %s", $targetProfile, $multimediaObject->getId()));
                        $this->jobService->addUniqueJob($master->getPath(), $targetProfile, 2, $multimediaObject, $master->getLanguage());
                    }
                }
            }
        }
    }
}

