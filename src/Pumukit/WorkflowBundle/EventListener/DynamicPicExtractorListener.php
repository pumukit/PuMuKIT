<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\DynamicPicExtractorService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;

class DynamicPicExtractorListener
{
    /** @var LoggerInterface */
    private $logger;
    /** @var DynamicPicExtractorService */
    private $dynamicPicExtractorService;
    private $enableDynamicPicExtract;
    private $trackTagAllowed;

    public function __construct(DynamicPicExtractorService $dynamicPicExtractorService, LoggerInterface $logger, bool $enableDynamicPicExtract = true, string $trackTagAllowed = 'master')
    {
        $this->dynamicPicExtractorService = $dynamicPicExtractorService;
        $this->logger = $logger;
        $this->enableDynamicPicExtract = $enableDynamicPicExtract;
        $this->trackTagAllowed = $trackTagAllowed;
    }

    public function onJobSuccess(JobEvent $event): void
    {
        if ($this->enableDynamicPicExtract) {
            $this->generateDynamicPic($event->getMultimediaObject(), $event->getTrack());
        }
    }

    public function generateDynamicPic(MultimediaObject $multimediaObject, Track $track): bool
    {
        if (!$track->containsTag($this->trackTagAllowed) || $track->isOnlyAudio()) {
            return false;
        }

        return $this->generateDynamicPicFromTrack($multimediaObject, $track);
    }

    private function generateDynamicPicFromTrack(MultimediaObject $multimediaObject, Track $track): bool
    {
        $outputMessage = $this->dynamicPicExtractorService->extract($multimediaObject, $track);
        if (!$outputMessage) {
            $message = $outputMessage.". MultimediaObject '".$multimediaObject->getId()."' with track '".$track->getId()."'";

            throw new \Exception($message);
        }

        $message = 'Extracted dynamic pic from track '.$track->getId().' into MultimediaObject "'.$multimediaObject->getId();
        $this->logger->info(self::class.'['.__FUNCTION__.'] '.$message.'"');

        return true;
    }
}
