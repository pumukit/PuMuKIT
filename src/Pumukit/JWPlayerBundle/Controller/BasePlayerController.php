<?php

namespace Pumukit\JWPlayerBundle\Controller;

use Pumukit\BasePlayerBundle\Controller\BasePlayerController as BasePlayerControllero;
use Pumukit\BasePlayerBundle\Services\IntroService;
use Pumukit\CoreBundle\Controller\PersonalControllerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BasePlayerController extends BasePlayerControllero implements PersonalControllerInterface
{
    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index", defaults={"no_channels": true} )
     * @Template("PumukitJWPlayerBundle:JWPlayer:player.html.twig")
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        /** @var EmbeddedBroadcastService */
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
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

        $playerParameters = $this->getPlayerParameters($request, $multimediaObject);

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
     * @Template("PumukitJWPlayerBundle:JWPlayer:player.html.twig")
     */
    public function magicAction(MultimediaObject $multimediaObject, Request $request)
    {
        /** @var MultimediaObjectService */
        $mmobjService = $this->get('pumukitschema.multimedia_object');
        if ($mmobjService->isPublished($multimediaObject, 'PUCHWEBTV')) {
            if ($mmobjService->hasPlayableResource($multimediaObject) && $multimediaObject->isPublicEmbeddedBroadcast()) {
                return $this->redirect($this->generateUrl('pumukit_videoplayer_index', ['id' => $multimediaObject->getId()]));
            }
        } elseif ((!in_array($multimediaObject->getStatus(), [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDDEN], true)) || !$multimediaObject->containsTagWithCod('PUCHWEBTV')) {
            return $this->render('PumukitWebTVBundle:Index:404notfound.html.twig');
        }

        if ($response = $this->validateAccess($request, $multimediaObject)) {
            return $response;
        }

        $track = $this->checkMultimediaObjectTracks($request, $multimediaObject);
        if ($track instanceof RedirectResponse) {
            return $track;
        }

        $playerParameters = $this->getPlayerParameters($request, $multimediaObject);

        return [
            'autostart' => $playerParameters['autoStart'],
            'intro' => $playerParameters['intro'],
            'object' => $multimediaObject,
            'when_dispatch_view_event' => $playerParameters['whenDispatchViewEvent'],
            'track' => $track,
            'magic_url' => true,
        ];
    }

    private function validateAccess(Request $request, MultimediaObject $multimediaObject)
    {
        /** @var EmbeddedBroadcastService */
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
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

    private function getPlayerParameters(Request $request, MultimediaObject $multimediaObject): array
    {
        /** @var IntroService */
        $basePlayerIntroService = $this->get('pumukit_baseplayer.intro');

        return [
            'autoStart' => $request->query->get('autostart', 'false'),
            'intro' => $basePlayerIntroService->getVideoIntroduction($multimediaObject, $request->query->getBoolean('intro')),
            'whenDispatchViewEvent' => $this->container->getParameter('pumukitplayer.when_dispatch_view_event'),
        ];
    }
}
