<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\DynamicPicExtractorService;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class DynamicPicExtractorListener
{
    private DynamicPicExtractorService $dynamicPicExtractorService;
    private LoggerInterface $logger;
    private bool $enableDynamicPicExtract;
    private string $trackTagAllowed;

    public function __construct(
        DynamicPicExtractorService $dynamicPicExtractorService,
        LoggerInterface $logger,
        bool $enableDynamicPicExtract = true,
        string $trackTagAllowed = 'master'
    ) {
        $this->dynamicPicExtractorService = $dynamicPicExtractorService;
        $this->logger = $logger;
        $this->enableDynamicPicExtract = $enableDynamicPicExtract;
        $this->trackTagAllowed = $trackTagAllowed;
    }

    public function onJobSuccess(JobEvent $event): void
    {
        if ($this->enableDynamicPicExtract && MultimediaObject::TYPE_VIDEO === $event->getMultimediaObject()->getType()) {
            $this->generateDynamicPic($event->getMultimediaObject(), $event->getMedia());
        }
    }

    public function generateDynamicPic(MultimediaObject $multimediaObject, MediaInterface $media): bool
    {
        if (!$media instanceof Track) {
            return false;
        }

        if (!$media->tags()->contains($this->trackTagAllowed) || $media->metadata()->isOnlyAudio()) {
            return false;
        }

        return $this->generateDynamicPicFromTrack($multimediaObject, $media);
    }

    private function generateDynamicPicFromTrack(MultimediaObject $multimediaObject, MediaInterface $media): bool
    {
        if (!$media instanceof Track) {
            return false;
        }

        if ($multimediaObject->hasDynamicPic()) {
            return false;
        }

        $outputMessage = $this->dynamicPicExtractorService->extract($multimediaObject, $media);
        if (!$outputMessage) {
            $message = $outputMessage.". MultimediaObject '".$multimediaObject->getId()."' with track '".$media->id()."'";

            $this->logger->warning($message);
        }

        $message = 'Extracted dynamic pic from track '.$media->id().' into MultimediaObject "'.$multimediaObject->getId();
        $this->logger->info(self::class.'['.__FUNCTION__.'] '.$message.'"');

        return true;
    }
}
