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

              $this->generateJobs($multimediaObject, $pubchannel->getCod());
            }
        }
    }


    /**
     * TODO add doc
     */
    private function generateJobs(MultimediaObject $multimediaObject, $pubChannelCod)
    {
        $jobs = array();
        foreach($this->profiles as $targetProfile => $profile) {
            $targets = $this->getTargets($profile['target']);
            if(((in_array($pubChannelCod, $targets['standard']))
                && ($multimediaObject->isOnlyAudio() == $profile['audio']))
               || (in_array($pubChannelCod, $targets['force']))
                && (!$multimediaObject->isOnlyAudio() || ($multimediaObject->isOnlyAudio() && $profile['audio']))){

                $master = $multimediaObject->getTrackWithTag("master");
                $this->logger->info(sprintf("WorkflowService creates new job (%s) for multimedia object %s", $targetProfile, $multimediaObject->getId()));
                $jobs[] = $this->jobService->addUniqueJob($master->getPath(), $targetProfile, 2, $multimediaObject, $master->getLanguage());
            }
        }
        return $jobs;
    }


    /**
     * Process the target string (See test)
     * "TAGA* TAGB, TAGC*, TAGD" => array('standard' => array('TAGB', 'TAGD'), 'force' => array('TAGA', 'TAGC'))
     * 
     * @return array
     */
    private function getTargets($targets)
    {
        $return = array('standard' => array(), 'force' => array());

        foreach(array_filter(preg_split('/[,\s]+/', $targets)) as $target) {
            if (substr($target, -1) == '*') {
                $return['force'][] = substr($target, 0 , -1);
            } else {
                $return['standard'][] = $target;
            }
        }

        return $return;
    }
}

