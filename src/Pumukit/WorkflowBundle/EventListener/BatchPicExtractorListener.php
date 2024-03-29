<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;

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

    public function onJobSuccess(JobEvent $event)
    {
        $this->generatePic($event->getMultimediaObject(), $event->getTrack());
    }

    private function generatePic(MultimediaObject $multimediaObject, Track $track)
    {
        if ($this->enable) {
            if (!$multimediaObject->isOnlyAudio() && !$track->isOnlyAudio()) {
                return $this->generatePicFromVideo($multimediaObject, $track);
            }
        }

        return false;
    }

    private function generatePicFromVideo(MultimediaObject $multimediaObject, Track $track)
    {
        $outputMessage = $this->picExtractorService->extractPicOnBatch($multimediaObject, $track);
        if (false !== strpos($outputMessage, 'Error')) {
            throw new \Exception($outputMessage.". MultimediaObject '".$multimediaObject->getId()."' with track '".$track->getId()."'");
        }
        $this->logger->info(self::class.'['.__FUNCTION__.'] '
                          .'Extracted pic from track '.
                          $track->getId().' into MultimediaObject "'
                          .$multimediaObject->getId().'"');

        return true;
    }
}
