<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\BasePlayerBundle\Services\IntroService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

abstract class BasePlayerController extends AbstractController
{
    public const MULTISTREAM_DEFAULT_TAG_TRACK_PRESENTER = 'presenter/delivery';
    public const MULTISTREAM_DEFAULT_TAG_TRACK_PRESENTATION = 'presentation/delivery';
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;
    protected $embeddedBroadcastService;
    protected $multimediaObjectService;
    protected $basePlayerIntroService;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EmbeddedBroadcastService $embeddedBroadcastService,
        MultimediaObjectService $multimediaObjectService,
        IntroService $basePlayerIntroService
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->basePlayerIntroService = $basePlayerIntroService;
    }

    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index" )
     */
    abstract public function indexAction(Request $request, MultimediaObject $multimediaObject);

    /**
     * @Route("/videoplayer/magic/{secret}", name="pumukit_videoplayer_magicindex")
     */
    abstract public function magicAction(Request $request, MultimediaObject $multimediaObject);

    public function validateAccess(Request $request, MultimediaObject $multimediaObject)
    {
        $password = $request->get('broadcast_password');
        /** @var User|null $user */
        $user = $this->getUser();
        $response = $this->embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $user, $password);
        if ($response instanceof Response) {
            return $response;
        }

        return false;
    }

    public function checkMultimediaObjectTracks(Request $request, MultimediaObject $multimediaObject)
    {
        $track = $request->query->has('track_id') ? $multimediaObject->getTrackById($request->query->get('track_id')) : $multimediaObject->getDisplayTrack();
        if ($track && $track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }
        if (!$track && null !== $url = $multimediaObject->getProperty('externalplayer')) {
            return $this->redirect($url);
        }

        return $track;
    }

    public function getMultimediaObjectMultiStreamTracks(MultimediaObject $multimediaObject, ?Track $track): array
    {
        if (!$track && $multimediaObject->isMultistream()) {
            $tracks = $multimediaObject->getFilteredTracksWithTags([
                self::MULTISTREAM_DEFAULT_TAG_TRACK_PRESENTER,
                self::MULTISTREAM_DEFAULT_TAG_TRACK_PRESENTATION,
            ]);
        } else {
            $tracks = [$track];
        }

        return $tracks;
    }

    protected function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null): void
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->eventDispatcher->dispatch($event, BasePlayerEvents::MULTIMEDIAOBJECT_VIEW);
    }
}
