<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

class HeadAndTailUpdateController extends AdminController implements NewAdminControllerInterface
{
    /**
     * @Security("is_granted('ROLE_ADD_HEAD_AND_TAIL')")
     * @Route("/head/update/{multimediaObject}/{isHead}", name="pumukit_newadmin_head_and_tail_set_head", methods={"POST"})
     */
    public function updateVideoHeadStatus(string $multimediaObject, string $isHead): JsonResponse
    {
        $documentManager = $this->get('doctrine.odm.mongodb.document_manager');
        $translator = $this->get('translator');
        $headAndTailService = $this->get('pumukit_schema.head_and_tail');

        $multimediaObject = $documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObject)]);
        if ($multimediaObject instanceof MultimediaObject) {
            $isHead = 'true' === $isHead;
            $multimediaObject->setHead($isHead);
            $documentManager->flush();

            $message = $translator->trans('Multimedia Object removed as head');
            if (!$isHead) {
                $headAndTailService->removeHeadElementOnAllMultimediaObjectsAndSeries($multimediaObject->getId());
            } else {
                $message = $translator->trans('Multimedia Object set as head');
            }

            return new JsonResponse(['success' => $message]);
        }

        return new JsonResponse(['success' => $translator->trans('Multimedia Object not found')]);
    }

    /**
     * @Security("is_granted('ROLE_ADD_HEAD_AND_TAIL')")
     * @Route("/tail/update/{multimediaObject}/{isTail}", name="pumukit_newadmin_head_and_tail_set_tail", methods={"POST"})
     */
    public function updateVideoTailStatus(string $multimediaObject, string $isTail): JsonResponse
    {
        $documentManager = $this->get('doctrine.odm.mongodb.document_manager');
        $translator = $this->get('translator');
        $headAndTailService = $this->get('pumukit_schema.head_and_tail');

        $multimediaObject = $documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObject)]);
        if ($multimediaObject instanceof MultimediaObject) {
            $isTail = 'true' === $isTail;
            $multimediaObject->setTail($isTail);
            $documentManager->flush();

            $message = $translator->trans('Multimedia Object removed as tail');
            if (!$isTail) {
                $headAndTailService->removeTailElementOnAllMultimediaObjectsAndSeries($multimediaObject->getId());
            } else {
                $message = $translator->trans('Multimedia Object set as tail');
            }

            return new JsonResponse(['success' => $message]);
        }

        return new JsonResponse(['success' => $translator->trans('Multimedia Object not found')]);
    }

    /**
     * @Security("is_granted('ROLE_ADD_HEAD_AND_TAIL')")
     * @Route("/headandtail/update/{multimediaObject}/{type}/{element}", name="pumukit_newadmin_head_and_tail_update", methods={"POST"})
     */
    public function updateHeadAndTail(string $type, string $multimediaObject, string $element): JsonResponse
    {
        $documentManager = $this->get('doctrine_mongodb.odm.document_manager');
        $multimediaObject = $documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObject)]);
        if (!$multimediaObject instanceof MultimediaObject) {
            return new JsonResponse(['error' => 'Multimedia Object not found']);
        }

        if (!in_array($type, ['head', 'tail'])) {
            return new JsonResponse(['error' => 'Type not supported']);
        }

        if ('default' === $element && 'head' === $type) {
            $multimediaObject->setVideoHead(null);
            $documentManager->flush();

            return new JsonResponse(['success' => 'Head removed successfully']);
        }

        if ('default' === $element && 'tail' === $type) {
            $multimediaObject->setVideoTail(null);
            $documentManager->flush();

            return new JsonResponse(['success' => 'Tail removed successfully']);
        }

        $headAndTailService = $this->get('pumukit_schema.head_and_tail');
        $headOrTail = $documentManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => new ObjectId($element),
        ]);

        if (!$headOrTail instanceof MultimediaObject) {
            return new JsonResponse(['error' => 'Head or tail not found']);
        }

        if ('head' === $type && !$headAndTailService->isHead($headOrTail)) {
            return new JsonResponse(['error' => 'Wrong head element']);
        }

        if ('tail' === $type && !$headAndTailService->isTail($headOrTail)) {
            return new JsonResponse(['error' => 'Wrong tail element']);
        }

        if ('head' === $type) {
            $multimediaObject->setVideoHead($headOrTail->getId());
            $documentManager->flush();

            return new JsonResponse(['success' => 'Head added successfully']);
        }

        if ('tail' === $type) {
            $multimediaObject->setVideoTail($headOrTail->getId());
            $documentManager->flush();

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
        $documentManager = $this->get('doctrine_mongodb.odm.document_manager');
        $series = $documentManager->getRepository(Series::class)->findOneBy(['_id' => new ObjectId($series)]);
        if (!$series instanceof Series) {
            return new JsonResponse(['error' => 'Series not found']);
        }

        if (!in_array($type, ['head', 'tail'])) {
            return new JsonResponse(['error' => 'Type not supported']);
        }

        if ('default' === $element && 'head' === $type) {
            $series->setVideoHead(null);
            $documentManager->flush();

            return new JsonResponse(['success' => 'Head removed successfully']);
        }

        if ('default' === $element && 'tail' === $type) {
            $series->setVideoTail(null);
            $documentManager->flush();

            return new JsonResponse(['success' => 'Tail removed successfully']);
        }

        $headAndTailService = $this->get('pumukit_schema.head_and_tail');
        $headOrTail = $documentManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => new ObjectId($element),
        ]);

        if (!$headOrTail instanceof MultimediaObject) {
            return new JsonResponse(['error' => 'Head or tail not found']);
        }

        if ('head' === $type && !$headAndTailService->isHead($headOrTail)) {
            return new JsonResponse(['error' => 'Wrong head element']);
        }

        if ('tail' === $type && !$headAndTailService->isTail($headOrTail)) {
            return new JsonResponse(['error' => 'Wrong tail element']);
        }

        if ('head' === $type) {
            $series->setVideoHead($headOrTail->getId());
            $documentManager->flush();

            return new JsonResponse(['success' => 'Head added successfully']);
        }

        if ('tail' === $type) {
            $series->setVideoTail($headOrTail->getId());
            $documentManager->flush();

            return new JsonResponse(['success' => 'Tail added successfully']);
        }

        return new JsonResponse(['error' => 'Unknown error. Please contact with administrator.']);
    }
}
