<?php

namespace Pumukit\WorkflowBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;

class JobGeneratorListener
{
    private $dm;
    private $logger;
    private $jobService;
    private $profiles;
    private $profileService;

    public function __construct(DocumentManager $documentManager, JobService $jobService, ProfileService $profileService, LoggerInterface $logger)
    {
        $this->dm = $documentManager;
        $this->jobService = $jobService;
        $this->logger = $logger;
        $this->profiles = $profileService->getProfiles();
        $this->profileService = $profileService;
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
        // Only for objects with master
        $master = $multimediaObject->getMaster(false);
        if (!$master) {
            return;
        }

        // Only for non multi-stream objects
        if ($multimediaObject->isMultistream()) {
            return;
        }

        $repository = $this->dm->getRepository(Tag::class);
        $tag = $repository->findOneByCod('PUBCHANNELS');
        if (!$tag) {
            return;
        }

        $profileName = $master->getProfileName();
        if (!$profileName || !isset($this->profiles[$profileName])) {
            return;
        }
        $profile = $this->profiles[$profileName];

        //NOTE: See TTK-7482
        foreach ($tag->getChildren() as $pubchannel) {
            if ($multimediaObject->containsTag($pubchannel)) {
                if (!$master->containsTag('ENCODED_'.$pubchannel->getCod()) && false === strpos($profile['target'], $pubchannel->getCod())) {
                    $master->addTag('ENCODED_'.$pubchannel->getCod());
                    $this->generateJobs($multimediaObject, $pubchannel->getCod());
                }
            }
        }
    }

    private function generateJobs(MultimediaObject $multimediaObject, $pubChannelCod)
    {
        $jobs = [];
        $default_profiles = $this->profileService->getDefaultProfiles();

        if ($this->containsTrackWithProfileWithTargetTag($multimediaObject, $pubChannelCod)) {
            $this->logger->info(sprintf(
                "JobGeneratorListener can't create a new job for multimedia object %s,".
                                        'because it already contains a track with a profile with this target (%s)',
                $multimediaObject->getId(),
                $pubChannelCod
            ));

            return $jobs;
        }

        foreach ($this->profiles as $targetProfile => $profile) {
            $targets = $this->getTargets($profile['target']);

            $track = $multimediaObject->getTrackWithTag('profile:'.$targetProfile);
            if ($track) {
                $this->logger->info(sprintf(
                    "JobGeneratorListener doesn't create a new job (%s) for multimedia object %s ".
                                            'because it already contains a track created with this profile',
                    $targetProfile,
                    $multimediaObject->getId()
                ));

                continue;
            }

            if (0 !== count($default_profiles)) {
                if (!isset($default_profiles[$pubChannelCod])) {
                    continue;
                }
                if (!$multimediaObject->isOnlyAudio() && false === strpos($default_profiles[$pubChannelCod]['video'], $targetProfile)) {
                    continue;
                }
                if ($multimediaObject->isOnlyAudio() && false === strpos($default_profiles[$pubChannelCod]['audio'], $targetProfile)) {
                    continue;
                }
            }

            if ((in_array($pubChannelCod, $targets['standard']))
               && ($multimediaObject->isOnlyAudio() == $profile['audio'])) {
                if (!$multimediaObject->isOnlyAudio() && 0 != $profile['resolution_ver']) {
                    $profileAspectRatio = $profile['resolution_hor'] / $profile['resolution_ver'];
                    $multimediaObjectAspectRatio = $multimediaObject->getTrackWithTag('master')->getAspectRatio();
                    if ((1.5 > $profileAspectRatio) !== (1.5 > $multimediaObjectAspectRatio)) {
                        $this->logger->info(sprintf(
                            "JobGeneratorListener can't create a new job (%s) for multimedia object %s using standard target, ".
                                                    'because a video profile aspect ratio(%f) is diferent to video aspect ratio (%f)',
                            $targetProfile,
                            $multimediaObject->getId(),
                            $profileAspectRatio,
                            $multimediaObjectAspectRatio
                        ));

                        continue;
                    }
                }

                $master = $multimediaObject->getTrackWithTag('master');
                $this->logger->info(sprintf('JobGeneratorListener creates new job (%s) for multimedia object %s using standard target', $targetProfile, $multimediaObject->getId()));
                $jobs[] = $this->jobService->addUniqueJob($master->getPath(), $targetProfile, 2, $multimediaObject, $master->getLanguage());
            }

            if (in_array($pubChannelCod, $targets['force'])) {
                $master = $multimediaObject->getTrackWithTag('master');
                $this->logger->info(sprintf('JobGeneratorListener creates new job (%s) for multimedia object %s using forced target', $targetProfile, $multimediaObject->getId()));
                $jobs[] = $this->jobService->addUniqueJob($master->getPath(), $targetProfile, 2, $multimediaObject, $master->getLanguage());
            }
        }

        return $jobs;
    }

    /**
     * Process the target string (See test)
     * "TAGA* TAGB, TAGC*, TAGD" => array('standard' => array('TAGB', 'TAGD'), 'force' => array('TAGA', 'TAGC')).
     *
     * @param mixed $targets
     *
     * @return array
     */
    private function getTargets($targets)
    {
        $return = ['standard' => [], 'force' => []];

        foreach (array_filter(preg_split('/[,\s]+/', $targets)) as $target) {
            if ('*' == substr($target, -1)) {
                $return['force'][] = substr($target, 0, -1);
            } else {
                $return['standard'][] = $target;
            }
        }

        return $return;
    }

    private function containsTrackWithProfileWithTargetTag(MultimediaObject $multimediaObject, $pubChannelCod)
    {
        foreach ($multimediaObject->getTracks() as $track) {
            $profileName = $track->getProfileName();
            if ($profileName && isset($this->profiles[$profileName])) {
                $targets = $this->getTargets($this->profiles[$profileName]['target']);
                if (in_array($pubChannelCod, $targets['standard'])) {
                    return true;
                }
            }
        }

        return false;
    }
}
