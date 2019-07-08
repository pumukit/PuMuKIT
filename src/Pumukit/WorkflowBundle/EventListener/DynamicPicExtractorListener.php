<?php

namespace Pumukit\WorkflowBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\DynamicPicExtractorService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;

/**
 * Class DynamicPicExtractorListener.
 */
class DynamicPicExtractorListener
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var DynamicPicExtractorService
     */
    private $dynamicPicExtractorService;

    private $enableDynamicPicExtract;
    private $trackTagAllowed;

    /**
     * DynamicPicExtractorListener constructor.
     *
     * @param DocumentManager            $documentManager
     * @param DynamicPicExtractorService $dynamicPicExtractorService
     * @param LoggerInterface            $logger
     * @param bool                       $enableDynamicPicExtract
     * @param string                     $trackTagAllowed
     */
    public function __construct(DocumentManager $documentManager, DynamicPicExtractorService $dynamicPicExtractorService, LoggerInterface $logger, $enableDynamicPicExtract = true, $trackTagAllowed = 'master')
    {
        $this->documentManager = $documentManager;
        $this->dynamicPicExtractorService = $dynamicPicExtractorService;
        $this->logger = $logger;
        $this->enableDynamicPicExtract = $enableDynamicPicExtract;
        $this->trackTagAllowed = $trackTagAllowed;
    }

    /**
     * @param JobEvent $event
     *
     * @throws \Exception
     */
    public function onJobSuccess(JobEvent $event)
    {
        if ($this->enableDynamicPicExtract) {
            $this->generateDynamicPic($event->getMultimediaObject(), $event->getTrack());
        }
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function generateDynamicPic(MultimediaObject $multimediaObject, Track $track)
    {
        if (!$track->containsTag($this->trackTagAllowed) || $track->isOnlyAudio()) {
            return false;
        }

        return $this->generateDynamicPicFromTrack($multimediaObject, $track);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     *
     * @throws \Exception
     *
     * @return bool
     */
    private function generateDynamicPicFromTrack(MultimediaObject $multimediaObject, Track $track)
    {
        $outputMessage = $this->dynamicPicExtractorService->extract($multimediaObject, $track);
        if (!$outputMessage) {
            $message = $outputMessage.". MultimediaObject '".$multimediaObject->getId()."' with track '".$track->getId()."'";

            throw new \Exception($message);
        }

        $message = 'Extracted dynamic pic from track '.$track->getId().' into MultimediaObject "'.$multimediaObject->getId();
        $this->logger->info(__CLASS__.'['.__FUNCTION__.'] '.$message.'"');

        return true;
    }
}
