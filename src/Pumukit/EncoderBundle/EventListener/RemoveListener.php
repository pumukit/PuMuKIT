<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\EventListener;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Event\TrackEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemoveListener
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        //NOTE: using container instead of job service to avoid ServiceCircularReferenceException.
        $this->container = $container;
    }

    public function postTrackRemove(TrackEvent $event)
    {
        $track = $event->getTrack();
        $trackPath = $track->getPath();
        $multimediaObject = $event->getMultimediaObject();

        $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
        $jobRepo = $dm->getRepository(Job::class);
        $jobService = $this->container->get('pumukitencoder.job');
        $relatedJob = $jobRepo->findOneBy(['path_end' => $trackPath, 'mm_id' => $multimediaObject->getId()]);
        if ($relatedJob) {
            $jobService->deleteJob($relatedJob->getId());
        }

        if ($this->checkIfMultimediaObjectHaveJustMasterTrack($multimediaObject)) {
            $this->removeEncodedTagOnMasterTrack($multimediaObject);
        }
    }

    public function checkIfMultimediaObjectHaveJustMasterTrack(MultimediaObject $multimediaObject): bool
    {
        $tracks = $multimediaObject->getTracks();
        if (1 === count($tracks)) {
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
