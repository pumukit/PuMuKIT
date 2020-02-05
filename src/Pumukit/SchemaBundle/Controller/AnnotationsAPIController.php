<?php

namespace Pumukit\SchemaBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\CoreBundle\Services\SerializerService;
use Pumukit\SchemaBundle\Document\Annotation;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\AnnotationsEvents;
use Pumukit\SchemaBundle\Event\AnnotationsUpdateEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

/**
 *  @Route("/annotation")
 */
class AnnotationsAPIController extends AbstractController
{
    /**
     * @Route("/annotations.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"}, methods={"GET"})
     */
    public function getAction(Request $request, DocumentManager $documentManager, SerializerService $serializer)
    {
        $episode = $request->get('episode');
        $type = $request->get('type');
        $day = $request->get('day');

        $limit = $request->get('limit') ?: 10;
        $offset = $request->get('offset') ?: 0;

        $annonRepo = $documentManager->getRepository(Annotation::class);
        $annonQB = $annonRepo->createQueryBuilder();

        if ($episode) {
            $annonQB->field('multimediaObject')->equals(new ObjectId($episode));
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
        $resAnnotations = $annonQB->getQuery()->execute()->toArray();
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

        $response = $serializer->dataSerialize($data, $request->getRequestFormat());

        return new Response($response);
    }

    /**
     * @Route("/{id}.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"}, methods={"GET"})
     */
    public function getByIdAction(Annotation $annotation, Request $request, SerializerService $serializer)
    {
        $data = [
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
        $response = $serializer->dataSerialize($data, $request->getRequestFormat());

        return new Response($response);
    }

    /**
     * @Route("/", methods={"PUT"})
     * @Security("has_role('ROLE_ACCESS_MULTIMEDIA_SERIES')")
     */
    public function createNewAction(Request $request, SerializerService $serializer, DocumentManager $documentManager, EventDispatcher $eventDispatcher)
    {
        $episode = $request->get('episode');
        $type = $request->get('type');
        $value = $request->get('value');
        $inPoint = $request->get('in');
        $outPoint = $request->get('out') ?: 100;
        $isPrivate = $request->get('isPrivate') ?: false;

        $annotation = new Annotation();
        $annotation->setMultimediaObject(new ObjectId($episode));
        $annotation->setType($type);
        $annotation->setValue($value);
        $annotation->setInPoint($inPoint);
        $annotation->setOutPoint($outPoint);
        $annotation->setPrivate($isPrivate);
        $annotation->setLength(0); //This field is not very useful.
        $annotation->setCreated(new \DateTime());
        $userId = $this->getUser() ? $this->getUser()->getId() : 'anonymous';
        $annotation->setUserId($userId);
        $session = new Session(); //Using symfony sessions instead of php session_id()
        $session = $session->getId();
        $annotation->setSession($session);

        $documentManager->persist($annotation);
        $documentManager->flush();

        $data = [
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
        $response = $serializer->dataSerialize($data, 'json');
        $event = new AnnotationsUpdateEvent($episode);
        $eventDispatcher->dispatch($event, AnnotationsEvents::UPDATE);

        return new Response($response);
    }

    /**
     * @Route("/{id}", methods={"PUT"})
     * @Security("has_role('ROLE_ACCESS_MULTIMEDIA_SERIES')")
     */
    public function editAction(Annotation $annotation, Request $request, SerializerService $serializer, DocumentManager $documentManager, EventDispatcher $eventDispatcher)
    {
        $value = $request->get('value');
        $annotation->setValue($value);
        $documentManager->persist($annotation);
        $documentManager->flush();
        $data = [
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
        $response = $serializer->dataSerialize($data, 'xml');
        $event = new AnnotationsUpdateEvent($annotation->getMultimediaObject());
        $eventDispatcher->dispatch( $event, AnnotationsEvents::UPDATE);

        return new Response($response);
    }

    /**
     * @Route("/{id}", methods={"DELETE"})
     * @Security("has_role('ROLE_ACCESS_MULTIMEDIA_SERIES')")
     */
    public function deleteAction(Annotation $annotation, DocumentManager $documentManager, SerializerService $serializer)
    {
        $documentManager->remove($annotation);
        $documentManager->flush();

        $response = $serializer->dataSerialize($annotation, 'xml');

        return new Response($response);
    }

    /**
     * @Route("/reset/{id}", methods={"DELETE"})
     * @Security("has_role('ROLE_ACCESS_MULTIMEDIA_SERIES')")
     */
    public function deleteAllAction(MultimediaObject $multimediaobject, DocumentManager $documentManager, SerializerService $serializer)
    {
        $annonRepo = $documentManager->getRepository(Annotation::class);
        $annonQB = $annonRepo->createQueryBuilder();
        $annonQB->field('multimediaObject')->equals(new ObjectId($multimediaobject->getId()));
        $annonQB->remove()->getQuery()->execute();

        $response = $serializer->serialize(['status' => 'ok'], 'xml');

        return new Response($response);
    }
}
