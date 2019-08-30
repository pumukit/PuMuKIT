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
        /** @var null|User $user */
        $user = $this->getUser();
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $user, $password);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $request->query->has('track_id') ?
               $multimediaObject->getTrackById($request->query->get('track_id')) :
               $multimediaObject->getDisplayTrack();

        if ($track && $track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        if (!$track && null !== $url = $multimediaObject->getProperty('externalplayer')) {
            return $this->redirect($url);
        }

        /** @var IntroService */
        $basePlayerIntroService = $this->get('pumukit_baseplayer.intro');

        return [
            'autostart' => $request->query->get('autostart', 'false'),
            'intro' => $basePlayerIntroService->getIntroForMultimediaObject($request->query->get('intro'), $multimediaObject->getProperty('intro')),
            'multimediaObject' => $multimediaObject,
            'object' => $multimediaObject,
            'when_dispatch_view_event' => $this->container->getParameter('pumukitplayer.when_dispatch_view_event'),
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
        } elseif ((
            MultimediaObject::STATUS_PUBLISHED != $multimediaObject->getStatus()
                 && MultimediaObject::STATUS_HIDDEN != $multimediaObject->getStatus()
        ) || !$multimediaObject->containsTagWithCod('PUCHWEBTV')) {
            return $this->render('PumukitWebTVBundle:Index:404notfound.html.twig');
        }

        /** @var EmbeddedBroadcastService */
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');

        $password = $request->get('broadcast_password');
        /** @var null|User $user */
        $user = $this->getUser();
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $user, $password);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $request->query->has('track_id') ?
               $multimediaObject->getTrackById($request->query->get('track_id')) :
               $multimediaObject->getDisplayTrack();

        if ($track && $track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        if (!$track && null !== $url = $multimediaObject->getProperty('externalplayer')) {
            return $this->redirect($url);
        }

        /** @var IntroService */
        $basePlayerIntroService = $this->get('pumukit_baseplayer.intro');

        return [
            'autostart' => $request->query->get('autostart', 'false'),
            'intro' => $basePlayerIntroService->getIntroForMultimediaObject($request->query->get('intro'), $multimediaObject->getProperty('intro')),
            'multimediaObject' => $multimediaObject,
            'object' => $multimediaObject,
            'when_dispatch_view_event' => $this->container->getParameter('pumukitplayer.when_dispatch_view_event'),
            'track' => $track,
            'magic_url' => true,
        ];
    }
}
