<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class BatchPicExtractorListener
{
    private $logger;
    private $picExtractorService;
    private $enable;

    public function __construct(PicExtractorService $picExtractorService, LoggerInterface $logger, $enable = true)
    {
        $this->picExtractorService = $picExtractorService;
        $this->logger = $logger;
        $this->enable = $enable;
    }

    public function onJobSuccess(JobEvent $event): void
    {
        $this->generatePic($event->getMultimediaObject(), $event->getMedia());
    }

    private function generatePic(MultimediaObject $multimediaObject, MediaInterface $track): void
    {
        if ($this->enable) {
            if (!$multimediaObject->isOnlyAudio() && !$track->metadatA()->isOnlyAudio()) {
                $this->generatePicFromVideo($multimediaObject, $track);
            }
        }
    }

    private function generatePicFromVideo(MultimediaObject $multimediaObject, MediaInterface $track)
    {
        $extracted = $this->picExtractorService->extractPicOnBatch($multimediaObject, $track);
        if (!$extracted) {
            throw new \Exception("Cannot extract pic on multimediaObject '".$multimediaObject->getId()."' with track '".$track->id()."'");
        }

        $this->logger->info(
            self::class.'['.__FUNCTION__.'] '
            .'Extracted pic from track '.
            $track->id().' into MultimediaObject "'
            .$multimediaObject->getId().'"'
        );

        return true;
    }
}
