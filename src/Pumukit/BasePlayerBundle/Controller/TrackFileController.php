<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerInterface;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\BasePlayerBundle\Services\SecureTokenService;
use Pumukit\BasePlayerBundle\Services\TrackDeliveryService;
use Pumukit\BasePlayerBundle\Services\TrackfileWithoutTokenPolicy;
use Pumukit\BasePlayerBundle\Services\ViewCounterService;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TrackFileController extends AbstractController
{
    private DocumentManager $documentManager;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private ?SecureTokenService $secureTokenService;
    private ?TrackDeliveryService $trackDeliveryService;
    private ?TrackfileWithoutTokenPolicy $trackfileWithoutTokenPolicy;
    private ?LoggerInterface $legacyTrackfileLogger;

    public function __construct(
        DocumentManager $documentManager,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        ?SecureTokenService $secureTokenService = null,
        ?TrackDeliveryService $trackDeliveryService = null,
        ?TrackfileWithoutTokenPolicy $trackfileWithoutTokenPolicy = null,
        ?LoggerInterface $legacyTrackfileLogger = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->secureTokenService = $secureTokenService;
        $this->trackDeliveryService = $trackDeliveryService;
        $this->trackfileWithoutTokenPolicy = $trackfileWithoutTokenPolicy;
        $this->legacyTrackfileLogger = $legacyTrackfileLogger;
    }

    /**
     * @param mixed $secret
     * @param mixed $secureDuration
     */
    #[Route('/trackfile/{id}.{ext}', name: 'pumukit_trackfile_index')]
    #[Route('/trackfile/{id}', name: 'pumukit_trackfile_index_no_ext')]
    public function indexAction(string $id, Request $request, DocumentManager $documentManager, string $pumukitPlayerWhenDispatchViewEvent, $secret, $secureDuration)
    {
        $invalidTokenReason = null;
        if (null !== $this->secureTokenService && !$this->secureTokenService->validateTokenFromRequest($request, $id)) {
            $invalidTokenReason = $this->invalidTokenReason($request, $id);
        }

        try {
            [$mmobj, $track] = $this->getMmobjAndTrack($documentManager, $id);
        } catch (\Exception $e) {
            if (null !== $invalidTokenReason) {
                $this->logInvalidToken($request, $id, $invalidTokenReason);

                return new Response('Invalid Token', Response::HTTP_NOT_FOUND);
            }

            $this->logger->error('Multimedia Object not found');

            return new Response('Not Found', Response::HTTP_NOT_FOUND);
        }

        if ($request->query->has('file')) {
            $this->logger->warning('Trackfile request with a file parameter', ['trackId' => $id]);

            return new Response('Not Found', Response::HTTP_NOT_FOUND);
        }

        if (null !== $invalidTokenReason) {
            $allowedReason = $this->trackfileWithoutTokenPolicy?->allowedReason($mmobj, $request);
            $context = [
                'trackId' => $id,
                'multimediaObjectId' => (string) $mmobj->getId(),
                'seriesId' => $this->trackfileWithoutTokenPolicy?->seriesId($mmobj) ?? 'none',
                'refererHost' => $this->trackfileWithoutTokenPolicy?->refererHost($request) ?? 'none',
                'tokenState' => $this->tokenState($invalidTokenReason),
            ];

            if (null === $allowedReason) {
                $this->legacyTrackfileLogger?->info('Trackfile rejected without a valid token', $context);
                $this->logInvalidToken($request, $id, $invalidTokenReason);

                return new Response('Invalid Token', Response::HTTP_NOT_FOUND);
            }

            $this->legacyTrackfileLogger?->info('Trackfile served without a valid token', $context + ['reason' => $allowedReason]);
        }

        if (null === $this->trackDeliveryService) {
            $this->logger->critical('Track delivery service is not configured', ['trackId' => $id]);

            return new Response('Service Unavailable', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->trackDeliveryService->response($track, $request->query->getBoolean('forcedl'), $request->getQueryString());
    }

    #[Route('/trackplayed/{id}', name: 'pumukit_trackplayed_index')]
    public function trackPlayedAction(Request $request, DocumentManager $documentManager, ViewCounterService $viewCounter, string $id): JsonResponse
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return new JsonResponse(['status' => 'error']);
        }

        [$mmobj, $track] = $this->getMmobjAndTrack($documentManager, $id);

        if (!str_starts_with($request->headers->get('referer', ''), $request->getSchemeAndHttpHost())) {
            return new JsonResponse(['status' => 'error']);
        }

        if (!$viewCounter->registerOnPlay($mmobj, $track)) {
            return new JsonResponse(['status' => 'error']);
        }

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/mediaplayed/{id}', name: 'pumukit_mediaplayed_index')]
    public function mediaPlayedAction(string $id, ViewCounterService $viewCounter): JsonResponse
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return new JsonResponse(['status' => 'error']);
        }

        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($id)]);
        if (!$multimediaObject instanceof MultimediaObject) {
            return new JsonResponse(['status' => 'error']);
        }

        if (!$viewCounter->registerOnPlay($multimediaObject)) {
            return new JsonResponse(['status' => 'error']);
        }

        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * @deprecated no lo llama nadie desde que la decision vive en ViewCounterService.
     *             Se mantiene por compatibilidad con controladores que lo extiendan.
     *             Su logica de multistream/Range es el unico matiz que el servicio
     *             todavia no cubre; si hace falta, moverlo alli antes de borrarlo.
     */
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
        if ($range && str_starts_with($range, 'bytes=0-')) {
            return true;
        }
        if (null !== $start && 0 == $start) {
            return true;
        }

        return false;
    }

    /**
     * @deprecated usar ViewCounterService::registerOnLoad()/registerOnPlay().
     *             Este metodo despacha sin comprobar la configuracion, que es
     *             justo lo que se queria centralizar. Se mantiene por
     *             compatibilidad con controladores que lo extiendan.
     */
    protected function dispatchViewEvent(MultimediaObject $multimediaObject, ?MediaInterface $track = null): void
    {
        $event = new ViewedEvent($multimediaObject, $track);

        $this->eventDispatcher->dispatch($event, BasePlayerEvents::MULTIMEDIAOBJECT_VIEW);
    }

    private function logInvalidToken(Request $request, string $id, string $reason): void
    {
        $this->logger->error('Invalid token', [
            'trackId' => $id,
            'referer' => $request->headers->get('referer', 'none'),
            'origin' => $request->headers->get('origin', 'none'),
            'clientIp' => $request->getClientIp(),
            'userAgent' => $request->headers->get('user-agent', 'none'),
            'path' => $request->getPathInfo(),
            'reason' => $reason,
        ]);
    }

    private function tokenState(string $reason): string
    {
        if ('missing token' === $reason || 'missing expires' === $reason) {
            return 'missing';
        }

        return str_starts_with($reason, 'expired') ? 'expired' : 'invalid';
    }

    private function invalidTokenReason(Request $request, string $id): string
    {
        if (!$request->query->get('token')) {
            return 'missing token';
        }

        $expires = $request->query->getInt('expires', 0);
        if (!$expires) {
            return 'missing expires';
        }
        if (time() > $expires) {
            return sprintf('expired %d seconds ago', time() - $expires);
        }

        $requestedResource = $request->query->get('resource');
        if (null !== $requestedResource && $requestedResource !== $id) {
            return sprintf('resource mismatch (requested %s)', $requestedResource);
        }

        return 'signature mismatch';
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
