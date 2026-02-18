<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerInterface;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\BasePlayerBundle\Services\SecureTokenService;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

class TrackFileController extends AbstractController
{
    private DocumentManager $documentManager;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private ?SecureTokenService $secureTokenService;
    private ?RateLimiterFactory $trackfileAccessLimiter;

    public function __construct(
        DocumentManager $documentManager,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        ?SecureTokenService $secureTokenService = null,
        ?RateLimiterFactory $trackfileAccessLimiter = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->secureTokenService = $secureTokenService;
        $this->trackfileAccessLimiter = $trackfileAccessLimiter;
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
        $clientIp = $request->getClientIp();

        if (null !== $this->trackfileAccessLimiter) {
            $limiter = $this->trackfileAccessLimiter->create($clientIp);

            if (false === $limiter->consume(1)->isAccepted()) {
                $this->logger->warning(sprintf(
                    'Rate limit exceeded for IP %s accessing trackfile',
                    $clientIp
                ));

                return new Response('Too many requests. Please try again later.', Response::HTTP_TOO_MANY_REQUESTS);
            }
        }

        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return new Response('Not Found', Response::HTTP_NOT_FOUND);
        }

        if (null !== $this->secureTokenService) {
            if (!$this->secureTokenService->validateTokenFromRequest($request, $id)) {
                $this->logger->warning(sprintf(
                    'Invalid or expired token for track %s from IP %s',
                    $id,
                    $clientIp
                ));

                return new Response('Not Found', Response::HTTP_NOT_FOUND);
            }
        } else {
            $this->logger->warning(sprintf(
                'SECURITY: SecureTokenService not configured - track %s accessed without validation from IP %s',
                $id,
                $clientIp
            ));
        }

        try {
            [$mmobj, $track] = $this->getMmobjAndTrack($documentManager, $id);
        } catch (\Exception $e) {
            return new Response('Not Found', Response::HTTP_NOT_FOUND);
        }

        if ($this->shouldIncreaseViews($request, $mmobj, $track, $pumukitPlayerWhenDispatchViewEvent)) {
            $this->dispatchViewEvent($mmobj, $track);
        }

        $filePath = $track->storage()->path()->path();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException("Track file not found: {$id}");
        }

        $response = new BinaryFileResponse($filePath);
        $response::trustXSendfileTypeHeader();

        if ($request->query->getBoolean('forcedl')) {
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } else {
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);
        }

        return $response;
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

        if (!str_starts_with($request->headers->get('referer'), $request->getSchemeAndHttpHost())) {
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
