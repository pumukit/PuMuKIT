<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OpencastController extends PlayerController implements WebTVController
{
    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function magicAction(MultimediaObject $multimediaObject, Request $request)
    {
        $array = $this->doAction($multimediaObject, $request);
        if ($array instanceof Response) {
            return $array;
        }

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
        if ($array instanceof Response) {
            return $array;
        }

        $array['cinema_mode'] = $this->getParameter('pumukit_web_tv.cinema_mode');

        return $this->render('PumukitWebTVBundle:MultimediaObject:index.html.twig',
                             $array
        );
    }

    public function doAction(MultimediaObject $multimediaObject, Request $request)
    {
        if (!$opencastUrl = $multimediaObject->getProperty('opencasturl')) {
            throw $this->createNotFoundException('The multimedia Object has no Opencast url!');
        }

        if ($this->container->hasParameter('pumukit_opencast.use_redirect') && $this->container->getParameter('pumukit_opencast.use_redirect')) {
            $event = new ViewedEvent($multimediaObject, null);
            $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
            if ($multimediaObject->getProperty('opencastinvert')) {
                $opencastUrl .= '&display=invert';
            }

            return $this->redirect($opencastUrl);
        }

        //Detect if it's mobile: (Refactor this using javascript... )
        $userAgent = $request->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $userAgentParserService = $this->get('pumukit_web_tv.useragent_parser');
        $isMobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isOldBrowser = $userAgentParserService->isOldBrowser($userAgent);

        $this->updateBreadcrumbs($multimediaObject);

        $editorChapters = $this->getChapterMarks($multimediaObject);

        return array(
            'intro' => $this->get('pumukit_baseplayer.intro')->getIntroForMultimediaObject($request->query->get('intro'), $multimediaObject->getProperty('intro')),
            'multimediaObject' => $multimediaObject,
            'is_old_browser' => $isOldBrowser,
            'is_mobile_device' => $isMobileDevice,
            'editor_chapters' => $editorChapters,
            'autostart' => $request->query->get('autostart', 'true'),
        );
    }
}
