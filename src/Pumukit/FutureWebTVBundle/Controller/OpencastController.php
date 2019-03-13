<?php

namespace Pumukit\FutureWebTVBundle\Controller;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OpencastController.
 */
class OpencastController extends PlayerController implements WebTVControllerInterface
{
    /**
     * @Template("PumukitFutureWebTVBundle:MultimediaObject:template.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function magicAction(MultimediaObject $multimediaObject, Request $request)
    {
        $array = $this->doAction($multimediaObject, $request);
        if ($array instanceof Response) {
            return $array;
        }

        $array['magic_url'] = true;
        $array['cinema_mode'] = $this->getParameter('pumukit_web_tv.cinema_mode');

        return $this->render(
            'PumukitFutureWebTVBundle:MultimediaObject:template.html.twig',
            $array
        );
    }

    /**
     * @Template("PumukitFutureWebTVBundle:MultimediaObject:template.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $array = $this->doAction($multimediaObject, $request);
        if ($array instanceof Response) {
            return $array;
        }

        $array['cinema_mode'] = $this->getParameter('pumukit_web_tv.cinema_mode');

        return $this->render(
            'PumukitFutureWebTVBundle:MultimediaObject:template.html.twig',
            $array
        );
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function doAction(MultimediaObject $multimediaObject, Request $request)
    {
        if (!$opencasturl = $multimediaObject->getProperty('opencasturl')) {
            throw $this->createNotFoundException('The multimedia Object has no Opencast url!');
        }

        if ($this->container->hasParameter('pumukit_opencast.use_redirect') && $this->container->getParameter('pumukit_opencast.use_redirect')) {
            $event = new ViewedEvent($multimediaObject, null);
            $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
            if ($invert = $multimediaObject->getProperty('opencastinvert')) {
                $opencasturl .= '&display=invert';
            }

            return $this->redirect($opencasturl);
        }

        //Detect if it's mobile: (Refactor this using javascript... )
        $userAgent = $request->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $userAgentParserService = $this->get('pumukit_web_tv.useragent_parser');
        $isMobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isOldBrowser = $userAgentParserService->isOldBrowser($userAgent);

        $this->updateBreadcrumbs($multimediaObject);

        $editorChapters = $this->getChapterMarks($multimediaObject);

        return [
            'intro' => $this->get('pumukit_baseplayer.intro')->getIntroForMultimediaObject(
                $request->query->get('intro'),
                $multimediaObject->getProperty('intro')
            ),
            'multimediaObject' => $multimediaObject,
            'is_old_browser' => $isOldBrowser,
            'is_mobile_device' => $isMobileDevice,
            'editor_chapters' => $editorChapters,
            'autostart' => $request->query->get('autostart', 'true'),
        ];
    }
}
