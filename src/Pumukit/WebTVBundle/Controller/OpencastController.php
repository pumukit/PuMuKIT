<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
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
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
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
        if ($this->container->hasParameter('pumukit.opencast.use_redirect') && $this->container->getParameter('pumukit.opencast.use_redirect')) {
            $event = new ViewedEvent($multimediaObject, $track);
            $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
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
            if ($response instanceof Response) {
                return $response;
            }

            $this->updateBreadcrumbs($multimediaObject);

            $editorChapters = $this->getChapterMarks($multimediaObject);

            return $this->render('PumukitWebTVBundle:MultimediaObject:index.html.twig',
                                 array('intro' => $this->getIntro($request->query->get('intro')),
                                       'multimediaObject' => $multimediaObject,
                                       'is_old_browser' => $isOldBrowser,
                                       'is_mobile_device' => $isMobileDevice,
                                       'editor_chapters' => $editorChapters,));
        }
    }
}
