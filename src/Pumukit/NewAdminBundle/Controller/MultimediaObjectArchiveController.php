<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\NewAdminBundle\Services\MultimediaObjectArchiveService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MultimediaObjectArchiveController extends AbstractController implements NewAdminControllerInterface
{
    private $documentManager;
    private $multimediaObjectArchiveService;

    public function __construct(DocumentManager $documentManager, MultimediaObjectArchiveService $multimediaObjectArchiveService)
    {
        $this->documentManager = $documentManager;
        $this->multimediaObjectArchiveService = $multimediaObjectArchiveService;
    }

    /**
     * @Route("/archive/mms/{id}", name="pumukit_multimedia_object_archive")
     */
    public function archiveModal(string $id): Response
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
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
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => new ObjectId($id),
        ]);

        if (!$multimediaObject instanceof MultimediaObject) {
            throw new \Exception('Multimedia Object not found');
        }

        $clonedMultimediaObject = $this->multimediaObjectArchiveService->archiveMultimediaObject($multimediaObject);

        return new JsonResponse(['success' => true, 'multimediaObject' => $clonedMultimediaObject->getId()]);
    }
}
