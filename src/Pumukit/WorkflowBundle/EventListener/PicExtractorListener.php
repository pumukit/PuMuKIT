<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Utils\SemaphoreUtils;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class PicExtractorListener
{
    private DocumentManager $dm;
    private LoggerInterface $logger;
    private PicExtractorService $picExtractorService;
    private bool $autoExtractPic;
    private ProfileService $profileService;
    private string $autoExtractPicPercentage;

    public function __construct(
        DocumentManager $documentManager,
        PicExtractorService $picExtractorService,
        LoggerInterface $logger,
        ProfileService $profileService,
        bool $autoExtractPic = true,
        string $autoExtractPicPercentage = '50%'
    ) {
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

        $semaphore = SemaphoreUtils::acquire(1000004);

        $this->generatePic($event->getMultimediaObject(), $event->getMedia());

        SemaphoreUtils::release($semaphore);
    }

    private function generatePic(MultimediaObject $multimediaObject, MediaInterface $media): void
    {
        $this->dm->refresh($multimediaObject);

        if (!$this->autoExtractPic && !$multimediaObject->getPics()->isEmpty()) {
            return;
        }

        try {
            if ($multimediaObject->isOnlyAudio() || $media->metadata()->isOnlyAudio()) {
                return;
            }

            $this->extractPic($multimediaObject, $media);
        } catch (\Exception $e) {
            $this->logger->error(
                self::class.'['.__FUNCTION__.'] '
                .'There was an error in extracting a pic for MultimediaObject "'
                .$multimediaObject->getId().'" from Track "'.$media->id()
                .'". Error message: '.$e->getMessage()
            );
        }
    }

    private function extractPic(MultimediaObject $multimediaObject, MediaInterface $media): void
    {
        $wasExtracted = $this->picExtractorService->extractPic($multimediaObject, $media, $this->autoExtractPicPercentage);
        if (!$wasExtracted) {
            throw new \Exception(
                "ERROR: Cannot extract PIC from MultimediaObject '".$multimediaObject->getId()."' with track '".$media->id()."'"
            );
        }
        $this->logger->info(
            self::class.'['.__FUNCTION__.'] '
            .'Extracted pic from track '.
            $media->id().' into MultimediaObject "'
            .$multimediaObject->getId().'"'
        );
    }
}
