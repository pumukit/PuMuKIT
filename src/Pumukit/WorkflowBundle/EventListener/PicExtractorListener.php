<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;

class PicExtractorListener
{
    private $dm;
    private $logger;
    private $picExtractorService;
    private $autoExtractPic;
    private $profileService;
    private $autoExtractPicPercentage;

    public function __construct(DocumentManager $documentManager, PicExtractorService $picExtractorService, LoggerInterface $logger, ProfileService $profileService, bool $autoExtractPic = true, string $autoExtractPicPercentage = '50%')
    {
        $this->dm = $documentManager;
        $this->picExtractorService = $picExtractorService;
        $this->logger = $logger;
        $this->autoExtractPic = $autoExtractPic;
        $this->profileService = $profileService;
        $this->autoExtractPicPercentage = $autoExtractPicPercentage;
    }

    public function onJobSuccess(JobEvent $event): void
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

    private function generatePic(MultimediaObject $multimediaObject, Track $track): bool
    {
        $this->dm->refresh($multimediaObject);

        if ($this->autoExtractPic && $multimediaObject->getPics()->isEmpty()) {
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

    private function generatePicFromVideo(MultimediaObject $multimediaObject, Track $track): bool
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
