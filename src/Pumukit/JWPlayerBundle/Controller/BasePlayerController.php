<?php

namespace Pumukit\JWPlayerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pumukit\SchemaBundle\Document\Track;

use Pumukit\BasePlayerBundle\Controller\BasePlayerController as BasePlayerControllero;

class BasePlayerController extends BasePlayerControllero
{
    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index" )
     * @Template("PumukitJWPlayerBundle:JWPlayer:index.html.twig")
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $response = $this->testBroadcast($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $request->query->has('track_id') ?
                 $multimediaObject->getTrackById($request->query->get('track_id')) :
                 $multimediaObject->getFilteredTrackWithTags(array('display'));

        $this->dispatchViewEvent($multimediaObject, $track);

        if($track && $track->containsTag("download")) {
            return $this->redirect($track->getUrl());
        }

        return array('autostart' => $request->query->get('autostart', 'true'),
                     'intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'track' => $track, );
    }

    /**
     * @Route("/videoplayer/opencast/{id}", name="pumukit_videoplayer_opencast" )
     * @Template("PumukitJWPlayerBundle:JWPlayer:index_opencast.html.twig")
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
                     'is_mobile_device' => $isMobileDevice,
                     'is_old_browser' => $isOldBrowser);
    }
}
