<?php

namespace Pumukit\JWPlayerBundle\Controller;

use Pumukit\BasePlayerBundle\Controller\BasePlayerController as BasePlayerControllero;
use Pumukit\BasePlayerBundle\Services\IntroService;
use Pumukit\CoreBundle\Controller\PersonalControllerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BasePlayerController extends BasePlayerControllero implements PersonalControllerInterface
{
    private $pumukitPlayerWhenDispatchViewEvent;

    public function __construct(EventDispatcherInterface $eventDispatcher, string $pumukitPlayerWhenDispatchViewEvent)
    {
        parent::__construct($eventDispatcher);
        $this->pumukitPlayerWhenDispatchViewEvent = $pumukitPlayerWhenDispatchViewEvent;
    }

    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index", defaults={"no_channels": true} )
     * @Template("@PumukitJWPlayer/JWPlayer/player.html.twig")
     */
    public function indexAction(Request $request, EmbeddedBroadcastService $embeddedBroadcastService, MultimediaObjectService $multimediaObjectService, IntroService $basePlayerIntroService, MultimediaObject $multimediaObject)
    {
        $password = $request->get('broadcast_password');
        /** @var User|null $user */
        $user = $this->getUser();
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $user, $password);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $this->checkMultimediaObjectTracks($request, $multimediaObject);
        if ($track instanceof RedirectResponse) {
            return $track;
        }
      
        $playerParameters = $this->getPlayerParameters($request, $basePlayerIntroService, $multimediaObject);

        return [
            'autostart' => $playerParameters['autoStart'],
            'intro' => $playerParameters['intro'],
            'multimediaObject' => $multimediaObject,
            'object' => $multimediaObject,
            'when_dispatch_view_event' => $playerParameters['whenDispatchViewEvent'],
            'track' => $track,
        ];
    }

    /**
     * @Route("/videoplayer/magic/{secret}", name="pumukit_videoplayer_magicindex", defaults={"show_hide": true, "no_channels": true} )
     * @Template("@PumukitJWPlayer/JWPlayer/player.html.twig")
     */
    public function magicAction(Request $request, EmbeddedBroadcastService $embeddedBroadcastService, MultimediaObjectService $multimediaObjectService, IntroService $basePlayerIntroService, MultimediaObject $multimediaObject)
    {
        if ($multimediaObjectService->isPublished($multimediaObject, 'PUCHWEBTV')) {
            if ($multimediaObjectService->hasPlayableResource($multimediaObject) && $multimediaObject->isPublicEmbeddedBroadcast()) {
                return $this->redirect($this->generateUrl('pumukit_videoplayer_index', ['id' => $multimediaObject->getId()]));
            }
        } elseif (!$multimediaObject->containsTagWithCod('PUCHWEBTV') || (!in_array($multimediaObject->getStatus(), [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDDEN], true))) {
            return $this->render('@PumukitWebTV/Index/404notfound.html.twig');
        }

        if ($response = $this->validateAccess($request, $embeddedBroadcastService, $multimediaObject)) {
            return $response;
        }

        $track = $this->checkMultimediaObjectTracks($request, $multimediaObject);
        if ($track instanceof RedirectResponse) {
            return $track;
        }

        $playerParameters = $this->getPlayerParameters($request, $basePlayerIntroService, $multimediaObject);

        return [
            'autostart' => $playerParameters['autoStart'],
            'intro' => $playerParameters['intro'],
            'object' => $multimediaObject,
            'when_dispatch_view_event' => $playerParameters['whenDispatchViewEvent'],
            'track' => $track,
            'magic_url' => true,
        ];
    }

    private function validateAccess(Request $request, EmbeddedBroadcastService $embeddedBroadcastService, MultimediaObject $multimediaObject)
    {
        $password = $request->get('broadcast_password');
        /** @var User|null $user */
        $user = $this->getUser();
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $user, $password);
        if ($response instanceof Response) {
            return $response;
        }

        return false;
    }

    private function checkMultimediaObjectTracks(Request $request, MultimediaObject $multimediaObject)
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

    private function getPlayerParameters(Request $request, IntroService $basePlayerIntroService, MultimediaObject $multimediaObject): array
    {
        return [
            'autoStart' => $request->query->get('autostart', 'false'),
            'intro' => $basePlayerIntroService->getVideoIntroduction($multimediaObject, $request->query->getBoolean('intro')),
            'whenDispatchViewEvent' => $this->pumukitPlayerWhenDispatchViewEvent,
        ];
    }
}
