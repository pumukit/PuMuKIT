<?php

namespace Pumukit\SchemaBundle\Listener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\EncoderBundle\Document\Job;

class RemoveListener
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        //NOTE: using container instead of tag service to avoid ServiceCircularReferenceException.
        $this->container = $container;
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $document = $args->getDocument();

        if ($document instanceof Series) {
            $seriesPicService = $this->container->get("pumukitschema.seriespic");
            foreach ($document->getPics() as $pic) {
                $document = $seriesPicService->removePicFromMultimediaObject($document, $pic->getId());
            }
        }

        if ($document instanceof MultimediaObject) {
            $dm = $this->container->get("doctrine_mongodb.odm.document_manager");
            $jobRepo = $dm->getRepository("PumukitEncoderBundle:Job");
            $executingJobs = $jobRepo->findByStatusAndMultimediaObjectId(Job::STATUS_EXECUTING, $document->getId());

            if (0 !== $executingJobs->count()) {
                throw new \Exception("Can not delete Multimedia Object with id '".$document->getId()."'.".
                                     " It has '".$executingJobs->count()."' jobs executing.");
            }

            $tagService = $this->container->get("pumukitschema.tag");
            foreach($document->getTags() as $tag) {
                $tagService->removeTagFromMultimediaObject($document, $tag->getId());
            }

            $jobService = $this->container->get("pumukitencoder.job");
            $allJobs = $jobRepo->findByMultimediaObjectId($document->getId());
            foreach ($allJobs as $job) {
                $jobService->deleteJob($job->getId());
            }

            $trackService = $this->container->get("pumukitschema.track");
            foreach ($document->getTracks() as $track) {
                if (!$track->containsTag('opencast')) {
                    $trackService->removeTrackFromMultimediaObject($document, $track->getId());
                }
            }

            $mmsPicService = $this->container->get("pumukitschema.mmspic");
            foreach ($document->getPics() as $pic) {
                $document = $mmsPicService->removePicFromMultimediaObject($document, $pic->getId());
            }

            $materialService = $this->container->get("pumukitschema.material");
            foreach ($document->getMaterials() as $material) {
                $document = $materialService->removeMaterialFromMultimediaObject($document, $material->getId());
            }
        }
    }
}
