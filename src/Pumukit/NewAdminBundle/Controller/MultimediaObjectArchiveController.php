<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MultimediaObjectArchiveController extends Controller implements NewAdminControllerInterface
{
    /**
     * @Route("/archive/mms/{id}", name="pumukit_multimedia_object_archive")
     */
    public function archiveModal(string $id): Response
    {
        $objectManager = $this->get('doctrine_mongodb.odm.document_manager');

        $multimediaObject = $objectManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => new ObjectId($id),
        ]);

        return $this->render('@PumukitNewAdmin/MultimediaObject/Menu/Modal/_archive.html.twig', [
            'multimediaObject' => $multimediaObject,
        ]);
    }

    /**
     * @Route("/archive/save/mms/{id}", methods="POST", name="pumukit_multimedia_object_save_and_archive")
     */
    public function saveAndArchive(string $id): JsonResponse
    {
        $objectManager = $this->get('doctrine_mongodb.odm.document_manager');

        $multimediaObject = $objectManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => new ObjectId($id),
        ]);

        if (!$multimediaObject instanceof MultimediaObject) {
            throw new \Exception('Multimedia Object not found');
        }

        $archiveService = $this->get('pumukitnewadmin.archive_service');

        $clonedMultimediaObject = $archiveService->archiveMultimediaObject($multimediaObject);

        return new JsonResponse(['success' => true, 'multimediaObject' => $clonedMultimediaObject->getId()]);
    }
}
