<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\EncoderBundle\Document\Job;
use Symfony\Component\Translation\TranslatorInterface;

class RemoveListener
{
    private $container;
    private $translator;

    public function __construct(ContainerInterface $container, TranslatorInterface $translator)
    {
        //NOTE: using container instead of tag service to avoid ServiceCircularReferenceException.
        $this->container = $container;
        $this->translator = $translator;
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $document = $args->getDocument();

        if ($document instanceof Series) {
            $seriesPicService = $this->container->get('pumukitschema.seriespic');
            foreach ($document->getPics() as $pic) {
                $document = $seriesPicService->removePicFromSeries($document, $pic->getId());
            }
        }

        if ($document instanceof MultimediaObject) {
            $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
            $jobRepo = $dm->getRepository(Job::class);
            $executingJobs = $jobRepo->findByStatusAndMultimediaObjectId(Job::STATUS_EXECUTING, $document->getId());

            if (0 !== $executingJobs->count()) {
                throw new \Exception(
                    $this->translator->trans('Can not delete Multimedia Object with id %videoId%. It has %jobsCount% jobs executing.',
                        [
                            '%videoId%' => $document->getId(),
                            '%jobsCount%' => $executingJobs->count(),
                        ])
                );
            }
            $mmsService = $this->container->get('pumukitschema.multimedia_object');
            $mmsService->removeFromAllPlaylists($document);

            $tagService = $this->container->get('pumukitschema.tag');
            foreach ($document->getTags() as $tag) {
                if ($document->containsTag($tag)) {
                    $tagService->removeTagFromMultimediaObject($document, $tag->getId());
                }
            }

            $jobService = $this->container->get('pumukitencoder.job');
            $allJobs = $jobRepo->findByMultimediaObjectId($document->getId());
            foreach ($allJobs as $job) {
                $jobService->deleteJob($job->getId());
            }

            $trackService = $this->container->get('pumukitschema.track');
            foreach ($document->getTracks() as $track) {
                $trackService->removeTrackFromMultimediaObject($document, $track->getId());
            }

            $mmsPicService = $this->container->get('pumukitschema.mmspic');
            foreach ($document->getPics() as $pic) {
                $document = $mmsPicService->removePicFromMultimediaObject($document, $pic->getId());
            }

            $materialService = $this->container->get('pumukitschema.material');
            foreach ($document->getMaterials() as $material) {
                $document = $materialService->removeMaterialFromMultimediaObject($document, $material->getId());
            }
        }

        if ($document instanceof Group) {
            $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
            $mmsService = $this->container->get('pumukitschema.multimedia_object');
            $embBroadcastService = $this->container->get('pumukitschema.embeddedbroadcast');
            $mmobjRepo = $dm->getRepository(MultimediaObject::class);
            $multimediaObjects = $mmobjRepo->findWithGroup($document);
            foreach ($multimediaObjects as $multimediaObject) {
                $mmsService->deleteGroup($document, $multimediaObject, false);
            }
            $multimediaObjects = $mmobjRepo->createQueryBuilder()
                ->field('embeddedBroadcast.groups')->in([new \MongoId($document->getId())])
                ->getQuery()
                ->execute();
            foreach ($multimediaObjects as $multimediaObject) {
                $embBroadcastService->deleteGroup($document, $multimediaObject, false);
            }
            $userService = $this->container->get('pumukitschema.user');
            $users = $userService->findWithGroup($document);
            foreach ($users as $user) {
                $userService->deleteGroup($document, $user, false);
            }
            $dm->flush();
        }
    }
}
