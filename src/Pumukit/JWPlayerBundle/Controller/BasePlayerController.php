<?php

namespace Pumukit\JWPlayerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\CoreBundle\Controller\PersonalController;
use Pumukit\BasePlayerBundle\Controller\BasePlayerController as BasePlayerControllero;

class BasePlayerController extends BasePlayerControllero implements PersonalController
{
    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index", defaults={"no_channels": true} )
     * @Template("PumukitJWPlayerBundle:JWPlayer:player.html.twig")
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $password = $request->get('broadcast_password');
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $this->getUser(), $password);
        if ($response instanceof Response) {
            return $response;
        }
        //Go to opencast
        if ($multimediaObject->getProperty('opencast')) {
            return $this->forward('PumukitBasePlayerBundle:BasePlayer:opencast', array('request' => $request, 'multimediaObject' => $multimediaObject));
        }

        $track = $request->query->has('track_id') ?
               $multimediaObject->getTrackById($request->query->get('track_id')) :
               $multimediaObject->getDisplayTrack();

        if ($track && $track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        return array('autostart' => $request->query->get('autostart', 'false'),
                     'intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'object' => $multimediaObject,
                     'track' => $track, );
    }

    /**
     * @Route("/videoplayer/magic/{secret}", name="pumukit_videoplayer_magicindex", defaults={"show_hide": true, "no_channels": true} )
     * @Template("PumukitJWPlayerBundle:JWPlayer:player.html.twig")
     */
    public function magicAction(MultimediaObject $multimediaObject, Request $request)
    {
        $mmobjService = $this->get('pumukitschema.multimedia_object');
        if ($mmobjService->isPublished($multimediaObject, 'PUCHWEBTV')) {
            if ($mmobjService->hasPlayableResource($multimediaObject) && $multimediaObject->isPublicEmbeddedBroadcast()) {
                return $this->redirect($this->generateUrl('pumukit_webtv_multimediaobject_index', array('id' => $multimediaObject->getId())));
            }
        } elseif (($multimediaObject->getStatus() != MultimediaObject::STATUS_PUBLISHED
                 && $multimediaObject->getStatus() != MultimediaObject::STATUS_HIDE
                 ) || !$multimediaObject->containsTagWithCod('PUCHWEBTV')) {
            return $this->render('PumukitWebTVBundle:Index:404notfound.html.twig');
        }

        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $password = $request->get('broadcast_password');
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $this->getUser(), $password);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $request->query->has('track_id') ?
               $multimediaObject->getTrackById($request->query->get('track_id')) :
               $multimediaObject->getDisplayTrack();

        if ($track && $track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        return array('autostart' => $request->query->get('autostart', 'false'),
                     'intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'object' => $multimediaObject,
                     'track' => $track,
                     'magic_url' => true, );
    }

    /**
     * @Route("/videoplayer/opencast/{id}", name="pumukit_videoplayer_opencast" )
     * @Template("PumukitJWPlayerBundle:JWPlayer:player_opencast.html.twig")
     */
    public function opencastAction(MultimediaObject $multimediaObject, Request $request)
    {
        //Detect if it's mobile: (Refactor this using javascript... )
        $userAgent = $this->getRequest()->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $userAgentParserService = $this->get('pumukit_web_tv.useragent_parser');
        $isMobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isOldBrowser = $userAgentParserService->isOldBrowser($userAgent);

        $this->dispatchViewEvent($multimediaObject);

        return array('intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'object' => $multimediaObject,
                     'is_mobile_device' => $isMobileDevice,
                     'is_old_browser' => $isOldBrowser, );
    }
}
