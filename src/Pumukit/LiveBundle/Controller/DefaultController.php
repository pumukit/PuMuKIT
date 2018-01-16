<?php

namespace Pumukit\LiveBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\LiveBundle\Document\Live;

class DefaultController extends Controller
{
    /**
     * @param Live    $live
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     * @Route("/live/{id}", name="pumukit_live_id")
     * @Template("PumukitLiveBundle:Default:index.html.twig")
     */
    public function indexAction(Live $live, Request $request)
    {
        $this->updateBreadcrumbs($live->getName(), 'pumukit_live_id', array('id' => $live->getId()));

        return $this->iframeAction($live, $request, false);
    }

    /**
     * @param Live    $live
     * @param Request $request
     * @param bool    $iframe
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     * @Route("/live/iframe/{id}", name="pumukit_live_iframe_id")
     * @Template("PumukitLiveBundle:Default:iframe.html.twig")
     */
    public function iframeAction(Live $live, Request $request, $iframe = true)
    {
        if ($live->getPasswd() && $live->getPasswd() !== $request->get('broadcast_password')) {
            return $this->render(
                $iframe ? 'PumukitLiveBundle:Default:iframepassword.html.twig' : 'PumukitLiveBundle:Default:indexpassword.html.twig',
                array('live' => $live, 'invalid_password' => boolval($request->get('broadcast_password')))
            );
        }

        $userAgent = $request->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isIE = $mobileDetectorService->version('IE');
        $versionIE = $isIE ? floatval($isIE) : 11.0;

        return array(
            'live' => $live,
            'mobile_device' => $mobileDevice,
            'isIE' => $isIE,
            'versionIE' => $versionIE,
        );
    }

    /**
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     * @Route("/live", name="pumukit_live")
     * @Template("PumukitLiveBundle:Default:index.html.twig")
     */
    public function defaultAction(Request $request)
    {
        $repo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitLiveBundle:Live');
        $live = $repo->findOneBy(array());

        if (!$live) {
            throw $this->createNotFoundException('The live channel does not exist');
        }

        $this->updateBreadcrumbs($live->getName(), 'pumukit_live', array('id' => $live->getId()));

        return $this->iframeAction($live, $request, false);
    }

    /**
     * @param       $title
     * @param       $routeName
     * @param array $routeParameters
     */
    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
    }

    /**
     * @param Live $live
     *
     * @return array
     * @Route("/live/playlist/{id}", name="pumukit_live_playlist_id", defaults={"_format": "xml"})
     * @Template("PumukitLiveBundle:Default:playlist.xml.twig")
     */
    public function playlistAction(Live $live)
    {
        $intro = $this->container->hasParameter('pumukit2.intro') ? $this->container->getParameter('pumukit2.intro') : null;

        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $mmobjsPlaylist = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy(array('properties.is_live_playlist' => true));

        $response = array('live' => $live);
        if ($mmobjsPlaylist) {
            $response['items'] = $mmobjsPlaylist;
        } elseif ($intro) {
            $response['items'] = $intro;
        } else {
            $response['items'] = '/bundles/pumukitlive/video/default.mp4';
        }

        return $response;
    }
}
