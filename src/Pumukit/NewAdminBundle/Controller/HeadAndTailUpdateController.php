<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\HeadAndTailService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class HeadAndTailUpdateController extends AdminController
{
    private $headAndTailService;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        HeadAndTailService $headAndTailService,
        SessionInterface $session,
        TranslatorInterface $translator
    ) {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService, $session, $translator);
        $this->headAndTailService = $headAndTailService;
    }

    /**
     * @Security("is_granted('ROLE_ADD_HEAD_AND_TAIL')")
     * @Route("/head/update/{multimediaObject}/{isHead}", name="pumukit_newadmin_head_and_tail_set_head", methods={"POST"})
     */
    public function updateVideoHeadStatus(string $multimediaObject, string $isHead): JsonResponse
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObject)]);
        if ($multimediaObject instanceof MultimediaObject) {
            $isHead = 'true' === $isHead;
            $multimediaObject->setHead($isHead);
            $this->documentManager->flush();

            $message = $this->translator->trans('Multimedia Object removed as head');
            if (!$isHead) {
                $this->headAndTailService->removeHeadElementOnAllMultimediaObjectsAndSeries($multimediaObject->getId());
            } else {
                $message = $this->translator->trans('Multimedia Object set as head');
            }

            return new JsonResponse(['success' => $message]);
        }

        return new JsonResponse(['success' => $this->translator->trans('Multimedia Object not found')]);
    }

    /**
     * @Security("is_granted('ROLE_ADD_HEAD_AND_TAIL')")
     * @Route("/tail/update/{multimediaObject}/{isTail}", name="pumukit_newadmin_head_and_tail_set_tail", methods={"POST"})
     */
    public function updateVideoTailStatus(string $multimediaObject, string $isTail): JsonResponse
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObject)]);
        if ($multimediaObject instanceof MultimediaObject) {
            $isTail = 'true' === $isTail;
            $multimediaObject->setTail($isTail);
            $this->documentManager->flush();

            $message = $this->translator->trans('Multimedia Object removed as tail');
            if (!$isTail) {
                $this->headAndTailService->removeTailElementOnAllMultimediaObjectsAndSeries($multimediaObject->getId());
            } else {
                $message = $this->translator->trans('Multimedia Object set as tail');
            }

            return new JsonResponse(['success' => $message]);
        }

        return new JsonResponse(['success' => $this->translator->trans('Multimedia Object not found')]);
    }

    /**
     * @Security("is_granted('ROLE_ADD_HEAD_AND_TAIL')")
     * @Route("/headandtail/update/{multimediaObject}/{type}/{element}", name="pumukit_newadmin_head_and_tail_update", methods={"POST"})
     */
    public function updateHeadAndTail(string $type, string $multimediaObject, string $element): JsonResponse
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObject)]);
        if (!$multimediaObject instanceof MultimediaObject) {
            return new JsonResponse(['error' => 'Multimedia Object not found']);
        }

        if (!in_array($type, ['head', 'tail'])) {
            return new JsonResponse(['error' => 'Type not supported']);
        }

        if ('default' === $element && 'head' === $type) {
            $multimediaObject->setVideoHead(null);
            $this->documentManager->flush();

            return new JsonResponse(['success' => 'Head removed successfully']);
        }

        if ('default' === $element && 'tail' === $type) {
            $multimediaObject->setVideoTail(null);
            $this->documentManager->flush();

            return new JsonResponse(['success' => 'Tail removed successfully']);
        }

        $headOrTail = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => new ObjectId($element),
        ]);

        if (!$headOrTail instanceof MultimediaObject) {
            return new JsonResponse(['error' => 'Head or tail not found']);
        }

        if ('head' === $type && !$this->headAndTailService->isHead($headOrTail)) {
            return new JsonResponse(['error' => 'Wrong head element']);
        }

        if ('tail' === $type && !$this->headAndTailService->isTail($headOrTail)) {
            return new JsonResponse(['error' => 'Wrong tail element']);
        }

        if ('head' === $type) {
            $multimediaObject->setVideoHead($headOrTail->getId());
            $this->documentManager->flush();

            return new JsonResponse(['success' => 'Head added successfully']);
        }

        if ('tail' === $type) {
            $multimediaObject->setVideoTail($headOrTail->getId());
            $this->documentManager->flush();

            return new JsonResponse(['success' => 'Tail added successfully']);
        }

        return new JsonResponse(['error' => 'Unknown error. Please contact with administrator.']);
    }

    /**
     * @Security("is_granted('ROLE_ADD_HEAD_AND_TAIL')")
     * @Route("/headandtail/series/update/{series}/{type}/{element}", name="pumukit_newadmin_head_and_tail_series_update", methods={"POST"})
     */
    public function updateSeriesHeadAndTail(string $type, string $series, string $element): JsonResponse
    {
        $series = $this->documentManager->getRepository(Series::class)->findOneBy(['_id' => new ObjectId($series)]);
        if (!$series instanceof Series) {
            return new JsonResponse(['error' => 'Series not found']);
        }

        if (!in_array($type, ['head', 'tail'])) {
            return new JsonResponse(['error' => 'Type not supported']);
        }

        if ('default' === $element && 'head' === $type) {
            $series->setVideoHead(null);
            $this->documentManager->flush();

            return new JsonResponse(['success' => 'Head removed successfully']);
        }

        if ('default' === $element && 'tail' === $type) {
            $series->setVideoTail(null);
            $this->documentManager->flush();

            return new JsonResponse(['success' => 'Tail removed successfully']);
        }

        $headOrTail = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => new ObjectId($element),
        ]);

        if (!$headOrTail instanceof MultimediaObject) {
            return new JsonResponse(['error' => 'Head or tail not found']);
        }

        if ('head' === $type && !$this->headAndTailService->isHead($headOrTail)) {
            return new JsonResponse(['error' => 'Wrong head element']);
        }

        if ('tail' === $type && !$this->headAndTailService->isTail($headOrTail)) {
            return new JsonResponse(['error' => 'Wrong tail element']);
        }

        if ('head' === $type) {
            $series->setVideoHead($headOrTail->getId());
            $this->documentManager->flush();

            return new JsonResponse(['success' => 'Head added successfully']);
        }

        if ('tail' === $type) {
            $series->setVideoTail($headOrTail->getId());
            $this->documentManager->flush();

            return new JsonResponse(['success' => 'Tail added successfully']);
        }

        return new JsonResponse(['error' => 'Unknown error. Please contact with administrator.']);
    }
}
