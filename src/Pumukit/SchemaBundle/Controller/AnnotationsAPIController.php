<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\CoreBundle\Services\SerializerService;
use Pumukit\SchemaBundle\Document\Annotation;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Event\AnnotationsEvents;
use Pumukit\SchemaBundle\Event\AnnotationsUpdateEvent;
use Pumukit\SchemaBundle\Services\MultimediaObjectVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/annotation')]
class AnnotationsAPIController extends AbstractController
{
    private $documentManager;
    private $serializer;
    private $eventDispatcher;

    public function __construct(DocumentManager $documentManager, SerializerService $serializer, EventDispatcherInterface $eventDispatcher)
    {
        $this->documentManager = $documentManager;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
    }

    #[Route('/annotations.{_format}', defaults: ['_format' => 'json'], requirements: ['_format' => 'json|xml'], methods: ['GET'])]
    public function getAction(Request $request)
    {
        $episode = $request->query->get('episode');
        $type = $request->query->get('type');
        $day = $request->query->get('day');

        $limit = (int) $request->query->get('limit') ?: 10;
        $offset = (int) $request->query->get('offset') ?: 0;

        $annonRepo = $this->documentManager->getRepository(Annotation::class);
        $annonQB = $annonRepo->createQueryBuilder();

        if ($episode) {
            $multimediaObject = $this->findMultimediaObject($episode);
            $this->denyAccessUnlessAllowed(MultimediaObjectVoter::PLAY, $multimediaObject);
            $annonQB->field('multimediaObject')->equals(new ObjectId($multimediaObject->getId()));
        } elseif (!$this->isGranted(PermissionProfile::SCOPE_GLOBAL) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        if ($type) {
            $annonQB->field('type')->equals($type);
        }

        if ($day) {
            $minDate = new \DateTime($day);
            $minDate->setTime(0, 0, 0);
            $maxDate = new \DateTime($day);
            $maxDate->setTime(23, 59, 59);
            $annonQB->field('created')->gte($minDate)->lte($maxDate);
        }

        $total = clone $annonQB;
        $total = $total->count()->getQuery()->execute();

        $annonQB->limit($limit)->skip($offset);
        $resAnnotations = $annonQB->getQuery()->execute();
        $annotations = [];
        foreach ($resAnnotations as $ann) {
            $annotations[] = $ann;
        }

        $data = [
            'annotations' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'annotation' => $annotations,
            ],
        ];

        $response = $this->serializer->dataSerialize($data, $request->getRequestFormat());

        return new Response($response);
    }

    #[Route('/{id}.{_format}', defaults: ['_format' => 'json'], requirements: ['_format' => 'json|xml'], methods: ['GET'])]
    public function getByIdAction(Request $request, Annotation $annotation)
    {
        $this->denyAccessUnlessAllowed(MultimediaObjectVoter::PLAY, $this->multimediaObjectOf($annotation));

        $data = $this->annotationData($annotation);
        $response = $this->serializer->dataSerialize($data, $request->getRequestFormat());

        return new Response($response);
    }

    #[Route('/', methods: ['PUT'])]
    #[IsGranted('ROLE_ACCESS_MULTIMEDIA_SERIES')]
    public function createNewAction(Request $request)
    {
        $episode = $request->query->get('episode', $request->request->get('episode'));
        $type = $request->query->get('type', $request->request->get('type'));
        $value = $request->query->get('value', $request->request->get('value'));
        $inPoint = $request->query->getInt('in');
        $outPoint = $request->query->get('out', $request->request->get('out')) ?: 100;
        $isPrivate = $request->query->get('isPrivate', $request->request->get('isPrivate')) ?: false;

        $multimediaObject = $this->findMultimediaObject($episode);
        $this->denyAccessUnlessAllowed(MultimediaObjectVoter::EDIT, $multimediaObject);

        $annotation = new Annotation();
        $annotation->setMultimediaObject(new ObjectId($multimediaObject->getId()));
        $annotation->setType($type);
        $annotation->setValue($value);
        $annotation->setInPoint($inPoint);
        $annotation->setOutPoint($outPoint);
        $annotation->setPrivate($isPrivate);
        $annotation->setLength(0); // This field is not very useful.
        $annotation->setCreated(new \DateTime());
        $user = $this->getUser();
        $userId = $user instanceof User ? $user->getId() : 'anonymous';
        $annotation->setUserId($userId);
        $session = new Session(); // Using symfony sessions instead of php session_id()
        $session = $session->getId();
        $annotation->setSession($session);

        $this->documentManager->persist($annotation);
        $this->documentManager->flush();

        $data = $this->annotationData($annotation);
        $response = $this->serializer->dataSerialize($data, 'json');
        $this->dispatchUpdate($multimediaObject);

        return new Response($response);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ACCESS_MULTIMEDIA_SERIES')]
    public function editAction(Request $request, Annotation $annotation)
    {
        $multimediaObject = $this->multimediaObjectOf($annotation);
        $this->denyAccessUnlessAllowed(MultimediaObjectVoter::EDIT, $multimediaObject);

        $value = $request->query->get('value', $request->request->get('value'));
        $annotation->setValue($value);
        $this->documentManager->persist($annotation);
        $this->documentManager->flush();
        $data = $this->annotationData($annotation);
        $response = $this->serializer->dataSerialize($data, 'xml');
        $this->dispatchUpdate($multimediaObject);

        return new Response($response);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ACCESS_MULTIMEDIA_SERIES')]
    public function deleteAction(Annotation $annotation)
    {
        $multimediaObject = $this->multimediaObjectOf($annotation);
        $this->denyAccessUnlessAllowed(MultimediaObjectVoter::EDIT, $multimediaObject);

        $data = $this->annotationData($annotation);
        $this->documentManager->remove($annotation);
        $this->documentManager->flush();
        $this->dispatchUpdate($multimediaObject);

        return new Response($this->serializer->dataSerialize($data, 'xml'));
    }

    #[Route('/reset/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ACCESS_MULTIMEDIA_SERIES')]
    public function deleteAllAction(MultimediaObject $multimediaobject)
    {
        $this->denyAccessUnlessAllowed(MultimediaObjectVoter::EDIT, $multimediaobject);

        $annonRepo = $this->documentManager->getRepository(Annotation::class);
        $annonQB = $annonRepo->createQueryBuilder();
        $annonQB->field('multimediaObject')->equals(new ObjectId($multimediaobject->getId()));
        $annonQB->remove()->getQuery()->execute();
        $this->dispatchUpdate($multimediaobject);

        $response = $this->serializer->dataSerialize(['status' => 'ok'], 'xml');

        return new Response($response);
    }

    private function findMultimediaObject(?string $objectId): MultimediaObject
    {
        $objectId = (string) $objectId;
        $repository = $this->documentManager->getRepository(MultimediaObject::class);
        $multimediaObject = preg_match('/^[0-9a-f]{24}$/i', $objectId) ? $repository->find($objectId) : null;
        $multimediaObject ??= $repository->findOneBy(['secret' => $objectId]);
        if (!$multimediaObject instanceof MultimediaObject) {
            throw new NotFoundHttpException('Multimedia object not found');
        }

        return $multimediaObject;
    }

    private function multimediaObjectOf(Annotation $annotation): MultimediaObject
    {
        return $this->findMultimediaObject((string) $annotation->getMultimediaObject());
    }

    private function denyAccessUnlessAllowed(string $attribute, MultimediaObject $multimediaObject): void
    {
        if (!$this->isGranted($attribute, $multimediaObject)) {
            throw new AccessDeniedHttpException();
        }
    }

    private function dispatchUpdate(MultimediaObject $multimediaObject): void
    {
        $this->eventDispatcher->dispatch(new AnnotationsUpdateEvent($multimediaObject->getId()), AnnotationsEvents::UPDATE);
    }

    private function annotationData(Annotation $annotation): array
    {
        return [
            'annotation' => [
                'annotationId' => $annotation->getId(),
                'mediapackageId' => $annotation->getMultimediaObject(),
                'userId' => $annotation->getUserId(),
                'sessionId' => $annotation->getSession(),
                'inpoint' => $annotation->getInPoint(),
                'outpoint' => $annotation->getOutPoint(),
                'length' => $annotation->getLength(),
                'type' => $annotation->getType(),
                'isPrivate' => $annotation->isPrivate(),
                'value' => $annotation->getValue(),
                'created' => $annotation->getCreated(),
            ],
        ];
    }
}
