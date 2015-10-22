<?php

namespace Pumukit\WebTVBundle\Controller;


use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\WebTVBundle\Controller\PlayerController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OpencastController extends PlayerController
{
  /**
   * @Route("/video_opencast/{id}", name="pumukit_webtv_opencast_index", defaults={"show_hide": true})
   * @Template("PumukitWebTVBundle:MultimediaObject:index_opencast.html.twig")
   */
  public function indexAction( MultimediaObject $multimediaObject, Request $request ){
    $response = $this->testBroadcast($multimediaObject, $request);
    if ($response instanceof Response) {
        return $response;
    }
    if(!$opencasturl =  $multimediaObject->getProperty('opencasturl')){
        throw $this->createNotFoundException('The multimedia Object has no Opencast url!');
    }

    $mmobjService = $this->get('pumukitschema.multimedia_object');
    if ($this->container->getParameter('pumukit.opencast.use_redirect')) {
        $mmobjService->incNumView($multimediaObject);
        $mmobjService->dispatch($multimediaObject);
        if ($invert = $multimediaObject->getProperty('opencastinvert')) {
            $opencasturl .= '&display=invert';
        }

        return $this->redirect($opencasturl);
    }

    //Detect if it's mobile: (Refactor this using javascript... )

    $userAgent = $this->getRequest()->headers->get('user-agent');
    $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
    $userAgentParserService = $this->get('pumukit_web_tv.useragent_parser');
    $isMobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
    $isOldBrowser = $userAgentParserService->isOldBrowser($userAgent);

    if (!$isMobileDevice) {
        $track = $request->query->has('track_id') ?
                 $multimediaObject->getTrackById($request->query->get('track_id')) :
                 $multimediaObject->getFilteredTrackWithTags(array('display'));

        if (!$track) {
            throw $this->createNotFoundException();
        }

        $response = $this->testBroadcast($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }

        $this->dispatchViewEvent($multimediaObject, $track);

        if ($track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        $this->updateBreadcrumbs($multimediaObject);

        return $this->render('PumukitWebTVBundle:MultimediaObject:index_opencast.html.twig',
                             array('autostart' => $request->query->get('autostart', 'true'),
                                   'intro' => $this->getIntro($request->query->get('intro')),
                                   'multimediaObject' => $multimediaObject,
                                   'is_old_browser' => $isOldBrowser,
                                   'is_mobile_device' => $isMobileDevice,
                                   'track' => $track, ));
    }
  }
}
