<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerInterface;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class TrackFileController extends AbstractController
{
    private DocumentManager $documentManager;

    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;

    public function __construct(
        DocumentManager $documentManager,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->documentManager = $documentManager;
        $this->logger = $logger;
    }

    /**
     * @Route("/trackfile/{id}.{ext}", name="pumukit_trackfile_index")
     * @Route("/trackfile/{id}", name="pumukit_trackfile_index_no_ext")
     *
     * @param mixed $secret
     * @param mixed $secureDuration
     */
    public function indexAction(string $id, Request $request, DocumentManager $documentManager, string $pumukitPlayerWhenDispatchViewEvent, $secret, $secureDuration)
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        [$mmobj, $track] = $this->getMmobjAndTrack($documentManager, $id);

        if ($this->shouldIncreaseViews($request, $mmobj, $track, $pumukitPlayerWhenDispatchViewEvent)) {
            $this->dispatchViewEvent($mmobj, $track);
        }

        if (!$track->storage()->url()->url()) {
            if ($request->query->getBoolean('forcedl')) {
                $response = new BinaryFileResponse($track->getPath());
                $response::trustXSendfileTypeHeader();
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

                return $response;
            }

            throw $this->createNotFoundException("Not mmobj found with the track id: {$id}");
        }

        if ($secret) {
            $timestamp = time() + $secureDuration;
            $hash = $this->getHash($track, $timestamp, $secret, $request->getClientIp());

            return $this->redirect($track->storage()->url()->url()."?md5={$hash}&expires={$timestamp}&".http_build_query($request->query->all(), '', '&'));
        }

        if ($request->query->all()) {
            return $this->redirect($track->storage()->url()->url().'?'.http_build_query($request->query->all()));
        }

        return $this->redirect($track->storage()->url()->url());
    }

    /**
     * @Route("/trackplayed/{id}", name="pumukit_trackplayed_index")
     */
    public function trackPlayedAction(Request $request, DocumentManager $documentManager, string $pumukitPlayerWhenDispatchViewEvent, string $id): JsonResponse
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return new JsonResponse(['status' => 'error']);
        }

        [$mmobj, $track] = $this->getMmobjAndTrack($documentManager, $id);

        if ('on_play' !== $pumukitPlayerWhenDispatchViewEvent) {
            return new JsonResponse(['status' => 'error']);
        }

        if (0 !== strpos($request->headers->get('referer'), $request->getSchemeAndHttpHost())) {
            return new JsonResponse(['status' => 'error']);
        }

        $this->dispatchViewEvent($mmobj, $track);

        return new JsonResponse(['status' => 'success']);
    }

    /**
     * @Route("/mediaplayed/{id}", name="pumukit_mediaplayed_index")
     */
    public function mediaPlayedAction(string $id): JsonResponse
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return new JsonResponse(['status' => 'error']);
        }

        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($id)]);
        if (!$multimediaObject instanceof MultimediaObject) {
            return new JsonResponse(['status' => 'error']);
        }

        $event = new ViewedEvent($multimediaObject);
        $this->eventDispatcher->dispatch($event, BasePlayerEvents::MULTIMEDIAOBJECT_VIEW);

        return new JsonResponse(['status' => 'ok']);
    }

    protected function getHash(MediaInterface $track, $timestamp, string $secret, string $ip)
    {
        $url = $track->storage()->url()->url();
        $path = parse_url($url, PHP_URL_PATH);

        return str_replace('=', '', strtr(base64_encode(md5("{$timestamp}{$path}{$ip} {$secret}", true)), '+/', '-_'));
    }

    protected function shouldIncreaseViews(Request $request, MultimediaObject $multimediaObject, MediaInterface $media, string $pumukitPlayerWhenDispatchViewEvent)
    {
        if ('on_load' !== $pumukitPlayerWhenDispatchViewEvent) {
            return false;
        }

        $isMultiStream = $multimediaObject->isMultistream();
        $haveOnlyDelivery = (count($multimediaObject->getTracksWithTag('display')) <= 2) && $multimediaObject->getTracksWithTag('sbs');
        $isDelivery = $media->tags()->containsTag('presentation/delivery');
        if ($isMultiStream && $isDelivery && !$haveOnlyDelivery) {
            return false;
        }

        $range = $request->headers->get('range');
        $start = $request->headers->get('start');
        if (!$range && !$start) {
            return true;
        }
        if ($range && 'bytes=0-' === substr($range, 0, 8)) {
            return true;
        }
        if (null !== $start && 0 == $start) {
            return true;
        }

        return false;
    }

    protected function dispatchViewEvent(MultimediaObject $multimediaObject, ?MediaInterface $track = null): void
    {
        $event = new ViewedEvent($multimediaObject, $track);

        $this->eventDispatcher->dispatch($event, BasePlayerEvents::MULTIMEDIAOBJECT_VIEW);
    }

    private function getMmobjAndTrack(DocumentManager $documentManager, string $id): array
    {
        $mmobjRepo = $documentManager->getRepository(MultimediaObject::class);

        $mmobj = $mmobjRepo->findOneByTrackId($id);
        if (!$mmobj instanceof MultimediaObject) {
            throw $this->createNotFoundException("Not mmobj found with the track id: {$id}");
        }

        $track = $mmobj->getTrackById($id);
        if ($track->isHide()) {
            $this->logger->warning('Trying to reproduce an hide track');
        }

        if (!$this->isGranted('play', $mmobj)) {
            throw $this->createNotFoundException("Not mmobj found with the public track id: {$id}");
        }

        return [
            $mmobj,
            $track,
        ];
    }
}
