<?php

namespace Pumukit\WorkflowBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;

class PicExtractorListener
{
    private $dm;
    private $logger;
    private $mmsPicService;
    private $picExtractorService;
    private $resourcesDir;
    private $defaultAudioPic;
    private $defaultAudioPicOriginalName;
    private $autoExtractPic;
    private $audioPicCopy;
    private $profileService;
    private $autoExtractPicPercentage;

    public function __construct(DocumentManager $documentManager, MultimediaObjectPicService $mmsPicService, PicExtractorService $picExtractorService, LoggerInterface $logger, $profileService, $autoExtractPic = true, $autoExtractPicPercentage = '50%')
    {
        $this->dm = $documentManager;
        $this->mmsPicService = $mmsPicService;
        $this->picExtractorService = $picExtractorService;
        $this->logger = $logger;
        $this->resourcesDir = realpath(__DIR__.'/../Resources/public/images');
        $this->defaultAudioPic = realpath($this->resourcesDir.'/sound_bn.png');
        $this->audioPicCopy = $this->resourcesDir.'/sound_bn_copy.png';
        $this->defaultAudioPicOriginalName = 'sound_bn.png';
        $this->autoExtractPic = $autoExtractPic;
        $this->profileService = $profileService;
        $this->autoExtractPicPercentage = $autoExtractPicPercentage;
    }

    public function onJobSuccess(JobEvent $event)
    {
        $profileName = $event->getJob()->getProfile();
        $profile = $this->profileService->getProfile($profileName);
        $generatePic = $profile['generate_pic'];

        if (!$generatePic) {
            return;
        }

        $SEMKey = 1234568;
        $seg = sem_get($SEMKey, 1, 0666, -1);
        sem_acquire($seg);
        $this->generatePic($event->getMultimediaObject(), $event->getTrack());

        sem_release($seg);
    }

    private function generatePic(MultimediaObject $multimediaObject, Track $track)
    {
        $this->dm->refresh($multimediaObject);

        if ($multimediaObject->getPics()->isEmpty() && $this->autoExtractPic) {
            try {
                if ($multimediaObject->isOnlyAudio() || $track->isOnlyAudio()) {
                    return false;
                }

                return $this->generatePicFromVideo($multimediaObject, $track);
            } catch (\Exception $e) {
                $this->logger->error(__CLASS__.'['.__FUNCTION__.'] '
                                    .'There was an error in extracting a pic for MultimediaObject "'
                                    .$multimediaObject->getId().'" from Track "'.$track->getId()
                                    .'". Error message: '.$e->getMessage());

                return false;
            }
        }

        return false;
    }

    private function generatePicFromVideo(MultimediaObject $multimediaObject, Track $track)
    {
        $outputMessage = $this->picExtractorService->extractPic($multimediaObject, $track, $this->autoExtractPicPercentage);
        if (false !== strpos($outputMessage, 'Error')) {
            throw new \Exception($outputMessage.". MultimediaObject '".$multimediaObject->getId()."' with track '".$track->getId()."'");
        }
        $this->logger->info(__CLASS__.'['.__FUNCTION__.'] '
                            .'Extracted pic from track '.
                            $track->getId().' into MultimediaObject "'
                            .$multimediaObject->getId().'"');

        return true;
    }
}
