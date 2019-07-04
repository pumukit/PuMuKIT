<?php

namespace Pumukit\EncoderBundle\EventListener;

use Pumukit\EncoderBundle\Document\Job;
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
    }
}
