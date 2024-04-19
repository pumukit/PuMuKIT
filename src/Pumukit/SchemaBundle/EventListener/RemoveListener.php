<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use MongoDB\BSON\ObjectId;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobRemover;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\MaterialService;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Services\SeriesPicService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Contracts\Translation\TranslatorInterface;

class RemoveListener
{
    private $documentManager;
    private $multimediaObjectService;
    private $materialService;
    private $multimediaObjectPicService;
    private $seriesPicService;
    private $tagService;
    private $embeddedBroadcastService;
    private $userService;
    private $translator;
    private JobRemover $jobRemover;

    public function __construct(
        DocumentManager $documentManager,
        MultimediaObjectService $multimediaObjectService,
        MaterialService $materialService,
        MultimediaObjectPicService $multimediaObjectPicService,
        SeriesPicService $seriesPicService,
        JobRemover $jobRemover,
        TagService $tagService,
        EmbeddedBroadcastService $embeddedBroadcastService,
        UserService $userService,
        TranslatorInterface $translator,
    ) {
        $this->documentManager = $documentManager;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->materialService = $materialService;
        $this->multimediaObjectPicService = $multimediaObjectPicService;
        $this->seriesPicService = $seriesPicService;
        $this->tagService = $tagService;
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->jobRemover = $jobRemover;
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $document = $args->getDocument();

        if ($document instanceof Series) {
            foreach ($document->getPics() as $pic) {
                $document = $this->seriesPicService->removePicFromSeries($document, $pic->getId());
            }
        }

        if ($document instanceof MultimediaObject) {
            $jobRepo = $this->documentManager->getRepository(Job::class);
            $executingJobs = $jobRepo->findBy(['status' => Job::STATUS_EXECUTING, 'mm_id' => $document->getId()]);

            $countExecutingJobs = count($executingJobs);
            if (0 !== $countExecutingJobs) {
                throw new \Exception(
                    $this->translator->trans(
                        'Can not delete Multimedia Object with id %videoId%. It has %jobsCount% jobs executing.',
                        [
                            '%videoId%' => $document->getId(),
                            '%jobsCount%' => $countExecutingJobs,
                        ]
                    )
                );
            }

            $this->multimediaObjectService->removeFromAllPlaylists($document);

            foreach ($document->getTags() as $tag) {
                if ($document->containsTag($tag)) {
                    $this->tagService->removeTagFromMultimediaObject($document, $tag->getId());
                }
            }

            $allJobs = $jobRepo->findByMultimediaObjectId($document->getId());
            foreach ($allJobs as $job) {
                $this->jobRemover->delete($job);
            }

            foreach ($document->getTracks() as $track) {
                $this->jobRemover->removeMedia($document, $track->getId());
            }

            foreach ($document->getPics() as $pic) {
                $document = $this->multimediaObjectPicService->removePicFromMultimediaObject($document, $pic->getId());
            }

            foreach ($document->getMaterials() as $material) {
                $document = $this->materialService->removeMaterialFromMultimediaObject($document, $material->getId());
            }
        }

        if ($document instanceof Group) {
            $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);
            $multimediaObjects = $mmobjRepo->findWithGroup($document);
            foreach ($multimediaObjects as $multimediaObject) {
                $this->multimediaObjectService->deleteGroup($document, $multimediaObject, false);
            }
            $multimediaObjects = $mmobjRepo->createQueryBuilder()
                ->field('embeddedBroadcast.groups')->in([new ObjectId($document->getId())])
                ->getQuery()
                ->execute()
            ;
            foreach ($multimediaObjects as $multimediaObject) {
                $this->embeddedBroadcastService->deleteGroup($document, $multimediaObject, false);
            }

            $users = $this->userService->findWithGroup($document);
            foreach ($users as $user) {
                $this->userService->deleteGroup($document, $user, false);
            }
            $this->documentManager->flush();
        }
    }
}
