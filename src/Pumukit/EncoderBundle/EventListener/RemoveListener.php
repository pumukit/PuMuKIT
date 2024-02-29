<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobRemover;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Event\TrackEvent;

class RemoveListener
{
    private DocumentManager $documentManager;
    private JobRemover $jobRemover;

    public function __construct(DocumentManager $documentManager, JobRemover $jobRemover)
    {
        $this->documentManager = $documentManager;
        $this->jobRemover = $jobRemover;
    }

    public function postTrackRemove(TrackEvent $event): void
    {
        $media = $event->getMedia();
        $trackPath = $media->storage()->path()->path();
        $multimediaObject = $event->getMultimediaObject();

        $jobRepo = $this->documentManager->getRepository(Job::class);
        $relatedJob = $jobRepo->findOneBy(['path_end' => $trackPath, 'mm_id' => $multimediaObject->getId()]);
        if ($relatedJob) {
            $this->jobRemover->delete($relatedJob);
        }

        if ($this->checkIfMultimediaObjectHaveJustMasterTrack($multimediaObject)) {
            $this->removeEncodedTagOnMasterTrack($multimediaObject);
        }
    }

    public function checkIfMultimediaObjectHaveJustMasterTrack(MultimediaObject $multimediaObject): bool
    {
        $tracks = $multimediaObject->getTracks();
        if (1 === (is_countable($tracks) ? count($tracks) : 0)) {
            $masterTrack = $multimediaObject->getMaster();
            if ($masterTrack) {
                return true;
            }
        }

        return false;
    }

    public function removeEncodedTagOnMasterTrack(MultimediaObject $multimediaObject): void
    {
        $masterTrack = $multimediaObject->getMaster();
        if ($masterTrack instanceof Track) {
            $masterTrack->removeTag('ENCODED_PUCHWEBTV');
        }
    }
}
