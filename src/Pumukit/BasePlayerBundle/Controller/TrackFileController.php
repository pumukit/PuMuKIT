<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class TrackFileController extends AbstractController
{
    /**
     * @Route("/trackfile/{id}.{ext}", name="pumukit_trackfile_index")
     * @Route("/trackfile/{id}", name="pumukit_trackfile_index_no_ext")
     */
    public function indexAction(string $id, Request $request, DocumentManager $documentManager, string $pumukitPlayerWhenDispatchViewEvent, $secret, $secureDuration)
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        [$mmobj, $track] = $this->getMmobjAndTrack($documentManager, $id);

        if ($this->shouldIncreaseViews($track, $request, $pumukitPlayerWhenDispatchViewEvent)) {
            $this->dispatchViewEvent($mmobj, $track);
        }

        if (!$track->getUrl()) {
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

            return $this->redirect($track->getUrl()."?md5={$hash}&expires={$timestamp}&".http_build_query($request->query->all(), '', '&'));
        }

        if ($request->query->all()) {
            return $this->redirect($track->getUrl().'?'.http_build_query($request->query->all()));
        }

        return $this->redirect($track->getUrl());
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

    protected function getHash(Track $track, $timestamp, string $secret, string $ip)
    {
        $url = $track->getUrl();
        $path = parse_url($url, PHP_URL_PATH);

        return str_replace('=', '', strtr(base64_encode(md5("{$timestamp}{$path}{$ip} {$secret}", true)), '+/', '-_'));
    }

    protected function shouldIncreaseViews(Track $track, Request $request, string $pumukitPlayerWhenDispatchViewEvent): bool
    {
        if ('on_load' !== $pumukitPlayerWhenDispatchViewEvent) {
            return false;
        }

        if ($track->containsTag('presentation/delivery')) {
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

    protected function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null): void
    {
        $event = new ViewedEvent($multimediaObject, $track);

        $eventDispatcher = new EventDispatcher();

        $eventDispatcher->dispatch($event, BasePlayerEvents::MULTIMEDIAOBJECT_VIEW);
    }

    private function getMmobjAndTrack(DocumentManager $documentManager, string $id): array
    {
        $mmobjRepo = $documentManager->getRepository(MultimediaObject::class);

        $mmobj = $mmobjRepo->findOneByTrackId($id);
        if (!$mmobj) {
            throw $this->createNotFoundException("Not mmobj found with the track id: {$id}");
        }

        $track = $mmobj->getTrackById($id);
        if ($track->isHide()) {
            $logger = $this->container->get('logger');
            $logger->warning('Trying to reproduce an hide track');
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
