<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\DynamicPicExtractorService;
use Pumukit\EncoderBundle\Services\ProfileService;
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
    private $profileService;

    public function __construct(DynamicPicExtractorService $dynamicPicExtractorService, LoggerInterface $logger, ProfileService $profileService, bool $enableDynamicPicExtract = true, string $trackTagAllowed = 'master')
    {
        $this->dynamicPicExtractorService = $dynamicPicExtractorService;
        $this->logger = $logger;
        $this->enableDynamicPicExtract = $enableDynamicPicExtract;
        $this->trackTagAllowed = $trackTagAllowed;
        $this->profileService = $profileService;
    }

    public function onJobSuccess(JobEvent $event): void
    {
        $profileName = $event->getJob()->getProfile();
        $profile = $this->profileService->getProfile($profileName);
        $generatePic = $profile['generate_pic'];

        if ($this->enableDynamicPicExtract && $generatePic) {
            $this->generateDynamicPic($event->getMultimediaObject(), $event->getTrack());
        }
    }

    public function generateDynamicPic(MultimediaObject $multimediaObject, Track $track): bool
    {
        if (!$track->containsTag($this->trackTagAllowed) || $track->isOnlyAudio()) {
            return false;
        }

        $jobs = [$multimediaObject->getProperty('executing_jobs'), $multimediaObject->getProperty('pending_jobs')];
        $jobsToMerge = array_filter($jobs, function ($arr) {
            return isset($arr) && !empty($arr);
        });

        $allJobs = array_merge(...$jobsToMerge);

        if (count($allJobs) > 2 && $track->containsTag('presentation/delivery')) {
            return false;
        }

        return $this->generateDynamicPicFromTrack($multimediaObject, $track);
    }

    private function generateDynamicPicFromTrack(MultimediaObject $multimediaObject, Track $track): bool
    {
        $outputMessage = $this->dynamicPicExtractorService->extract($multimediaObject, $track);
        if (!$outputMessage) {
            $message = $outputMessage.". MultimediaObject '".$multimediaObject->getId()."' with track '".$track->getId()."'";

            $this->logger->warning($message);
        }

        $message = 'Extracted dynamic pic from track '.$track->getId().' into MultimediaObject "'.$multimediaObject->getId();
        $this->logger->info(self::class.'['.__FUNCTION__.'] '.$message.'"');

        return true;
    }
}
