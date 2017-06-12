<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class OpencastController extends PlayerController implements WebTVController
{
    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function magicAction(MultimediaObject $multimediaObject, Request $request)
    {
        $array = $this->doAction($multimediaObject, $request);
        $array['magic_url'] = true;
        $array['cinema_mode'] = $this->getParameter('pumukit_web_tv.cinema_mode');

        return $this->render('PumukitWebTVBundle:MultimediaObject:index.html.twig',
                             $array
        );
    }

    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $array = $this->doAction($multimediaObject, $request);
        $array['cinema_mode'] = $this->getParameter('pumukit_web_tv.cinema_mode');

        return $this->render('PumukitWebTVBundle:MultimediaObject:index.html.twig',
                             $array
        );
    }

    public function doAction(MultimediaObject $multimediaObject, Request $request)
    {
        if (!$opencasturl = $multimediaObject->getProperty('opencasturl')) {
            throw $this->createNotFoundException('The multimedia Object has no Opencast url!');
        }

        $mmobjService = $this->get('pumukitschema.multimedia_object');
        if ($this->container->hasParameter('pumukit_opencast.use_redirect') && $this->container->getParameter('pumukit_opencast.use_redirect')) {
            $event = new ViewedEvent($multimediaObject, null);
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

        $this->updateBreadcrumbs($multimediaObject);

        $editorChapters = $this->getChapterMarks($multimediaObject);

        return array(
            'intro' => $this->getIntro($request->query->get('intro')),
            'multimediaObject' => $multimediaObject,
            'is_old_browser' => $isOldBrowser,
            'is_mobile_device' => $isMobileDevice,
            'editor_chapters' => $editorChapters,
            'autostart' => $request->query->get('autostart', 'true'),
        );
    }
}
