<?php

namespace Pumukit\WorkflowBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class JobGeneratorListener
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

        $repository = $this->dm->getRepository('PumukitSchemaBundle:Tag');
        $tag = $repository->findOneByCod("PUBCHANNELS");
        if(!$tag) return;

        foreach($tag->getChildren() as $pubchannel) {
            if(($multimediaObject->containsTag($pubchannel))
               && ($master)) {

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
            
            $track = $multimediaObject->getTrackWithTag('profile:'.$targetProfile);
            if ($track) {
                $this->logger->info(sprintf("JobGeneratorListener doesn't create a new job (%s) for multimedia object %s ".
                                            "because it already contains a track created with this profile", 
                                            $targetProfile, $multimediaObject->getId()));
                continue;
            }

            if((in_array($pubChannelCod, $targets['standard']))
               && ($multimediaObject->isOnlyAudio() == $profile['audio'])){

                if (!$multimediaObject->isOnlyAudio() && 0 != $profile['resolution_ver']) {
                    $profileAspectRatio = $profile['resolution_hor']/$profile['resolution_ver'];
                    $multimediaObjectAspectRatio = $multimediaObject->getTrackWithTag("master")->getAspectRatio();
                    if ((1.5 > $profileAspectRatio) !== (1.5 > $multimediaObjectAspectRatio)) {
                        $this->logger->info(sprintf("JobGeneratorListener can't create a new job (%s) for multimedia object %s using standard target, ".
                                                    "because a video profile aspect ratio(%f) is diferent to video aspect ratio (%f)",
                                                    $targetProfile, $multimediaObject->getId(), $profileAspectRatio, $multimediaObjectAspectRatio));

                        continue;
                    }
                }
                
                $master = $multimediaObject->getTrackWithTag("master");
                $this->logger->info(sprintf("JobGeneratorListener creates new job (%s) for multimedia object %s using standard target", $targetProfile, $multimediaObject->getId()));
                $jobs[] = $this->jobService->addUniqueJob($master->getPath(), $targetProfile, 2, $multimediaObject, $master->getLanguage());
            }
            
            if(in_array($pubChannelCod, $targets['force'])) {

                if ($multimediaObject->isOnlyAudio() && !$profile['audio']){
                    $this->logger->info(sprintf("JobGeneratorListener can't create a new job (%s) for multimedia object %s using forced target, because a video profile can't be created from an audio",
                                                $targetProfile, $multimediaObject->getId()));
                    continue;
                }
            
                $master = $multimediaObject->getTrackWithTag("master");
                $this->logger->info(sprintf("JobGeneratorListener creates new job (%s) for multimedia object %s using forced target", $targetProfile, $multimediaObject->getId()));
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

