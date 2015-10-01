<?php

namespace Pumukit\Responsive\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\WebTVBundle\Event\ViewedEvent;
use Pumukit\WebTVBundle\Event\WebTVEvents;

class MultimediaObjectController extends Controller
{
    /**
     * @Route("/video/{id}", name="pumukit_responsive_webtv_multimediaobject_index")
     * @Template("PumukitResponsiveWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $response = $this->preExecute($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }

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

        $this->incNumView($multimediaObject, $track);
        $this->dispatch($multimediaObject, $track);

        if ($track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        $this->updateBreadcrumbs($multimediaObject);

        return array('autostart' => $request->query->get('autostart', 'true'),
                     'intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'track' => $track, );
    }

    /**
     * @Route("/iframe/{id}", name="pumukit_responsive_webtv_multimediaobject_iframe")
     * @Template()
     */
    public function iframeAction(MultimediaObject $multimediaObject, Request $request)
    {
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

        $this->incNumView($multimediaObject, $track);
        $this->dispatch($multimediaObject, $track);

        if ($track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        return array('autostart' => $request->query->get('autostart', 'true'),
                     'intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'track' => $track, );
    }

    /**
     * @Route("/video/magic/{secret}", name="pumukit_responsive_webtv_multimediaobject_magicindex", defaults={"filter": false})
     * @Template("PumukitResponsiveWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function magicIndexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $response = $this->preExecute($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $request->query->has('track_id') ?
                 $multimediaObject->getTrackById($request->query->get('track_id')) :
                 $multimediaObject->getTrackWithTag('display');

        $this->incNumView($multimediaObject, $track);
        $this->dispatch($multimediaObject, $track);

        if ($track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        $this->updateBreadcrumbs($multimediaObject);

        return array('autostart' => $request->query->get('autostart', 'true'),
                     'intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'track' => $track, );
    }

    /**
     * @Template("PumukitResponsiveWebTVBundle:MultimediaObject:mini_mmobjs.html.twig")
     */
    public function seriesAction(MultimediaObject $multimediaObject, $box_title)
    {
        $series = $multimediaObject->getSeries();
        $multimediaObjects = $series->getMultimediaObjects();

        $tagRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:Tag');
        $unescoTag = $tagRepo->findOneByCod('UNESCO');

        return array('series' => $series,
                     'multimediaObjects' => $multimediaObjects,
                     'unescoTag' => $unescoTag,
                     'box_title' => $box_title, );
    }

    /**
     * @Template("PumukitResponsiveWebTVBundle:MultimediaObject:mini_mmobjs.html.twig")
     */
    public function relatedAction(MultimediaObject $multimediaObject, $box_title)
    {
        $mmobjRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $relatedMms = $mmobjRepo->findRelatedMultimediaObjects($multimediaObject);

        $tagRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:Tag');
        $unescoTag = $tagRepo->findOneByCod('UNESCO');

        return array('multimediaObjects' => $relatedMms,
                     'unescoTag' => $unescoTag,
                     'box_title' => $box_title, );
    }

    protected function getIntro($queryIntro = false)
    {
        $hasIntro = $this->container->hasParameter('pumukit2.intro');

        if ($queryIntro && filter_var($queryIntro, FILTER_VALIDATE_URL)) {
            $intro = $queryIntro;
        } elseif ($hasIntro) {
            $intro = $this->container->getParameter('pumukit2.intro');
        } else {
            $intro = false;
        }

        return $intro;
    }

    protected function incNumView(MultimediaObject $multimediaObject, Track $track = null)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $multimediaObject->incNumview();
        $track && $track->incNumview();
        $dm->persist($multimediaObject);
        $dm->flush();
    }

    protected function dispatch(MultimediaObject $multimediaObject, Track $track = null)
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->get('event_dispatcher')->dispatch(WebTVEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }

    protected function updateBreadcrumbs(MultimediaObject $multimediaObject)
    {
        $breadcrumbs = $this->get('pumukit_responsive_web_tv.breadcrumbs');
        $breadcrumbs->addMultimediaObject($multimediaObject);
    }

    public function preExecute(MultimediaObject $multimediaObject, Request $request)
    {
        if ($opencasturl = $multimediaObject->getProperty('opencasturl')) {
            $response = $this->testBroadcast($multimediaObject, $request);
            if ($response instanceof Response) {
                return $response;
            }
            if ($this->container->getParameter('pumukit.opencast.use_redirect')) {
                $this->incNumView($multimediaObject);
                $this->dispatch($multimediaObject);
                if ($invert = $multimediaObject->getProperty('opencastinvert')) {
                    $opencasturl .= '&display=invert';
                }

                return $this->redirect($opencasturl);
            }

            //Detect if it's mobile: (Refactor this using javascript... )

            $userAgent = $this->getRequest()->headers->get('user-agent');
            $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
            $isMobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
            $isOldBrowser = $this->getIsOldBrowser($userAgent);

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

                $this->incNumView($multimediaObject, $track);
                $this->dispatch($multimediaObject, $track);

                if ($track->containsTag('download')) {
                    return $this->redirect($track->getUrl());
                }

                $this->updateBreadcrumbs($multimediaObject);

                return $this->render('PumukitResponsiveWebTVBundle:MultimediaObject:index_opencast.html.twig',
                                     array('autostart' => $request->query->get('autostart', 'true'),
                                           'intro' => $this->getIntro($request->query->get('intro')),
                                           'multimediaObject' => $multimediaObject,
                                           'is_old_browser' => $isOldBrowser,
                                           'is_mobile_device' => $isMobileDevice,
                                           'track' => $track, ));
            }
        }
    }

    public function testBroadcast(MultimediaObject $multimediaObject, Request $request)
    {
        if (($broadcast = $multimediaObject->getBroadcast()) &&
            (Broadcast::BROADCAST_TYPE_PUB !== $broadcast->getBroadcastTypeId()) &&
            ((!($broadcastName = $request->headers->get('PHP_AUTH_USER', false))) ||
              ($request->headers->get('PHP_AUTH_PW') !== $broadcast->getPasswd()) ||
              ($broadcastName !== $broadcast->getName()))) {
            $seriesUrl = $this->generateUrl('pumukit_webtv_series_index', array('id' => $multimediaObject->getSeries()->getId()), true);
            $redReq = new RedirectResponse($seriesUrl, 302);

            return new Response($redReq->getContent(), 401, array('WWW-Authenticate' => 'Basic realm="Resource not public."'));
        }

        if ($broadcast && (Broadcast::BROADCAST_TYPE_PRI === $broadcast->getBroadcastTypeId())) {
            return new Response($this->render('PumukitResponsiveWebTVBundle:Index:403forbidden.html.twig', array()), 403);
        }

        return true;
    }

    //Refactor this using javascript.
    private function getIsOldBrowser($userAgent)
    {
        $isOldBrowser = false;
        $webExplorer = $this->getWebExplorer($userAgent);
        $version = $this->getVersion($userAgent, $webExplorer);
        if (($webExplorer == 'IE') || ($webExplorer == 'MSIE') || $webExplorer == 'Firefox' || $webExplorer == 'Opera' || ($webExplorer == 'Safari' && $version < 4)) {
            $isOldBrowser = true;
        }

        return $isOldBrowser;
    }

    private function getWebExplorer($userAgent)
    {
        if (preg_match('/MSIE/i', $userAgent)) {
            $webExplorer = 'MSIE';
        }
        if (preg_match('/Opera/i', $userAgent)) {
            $webExplorer = 'Opera';
        }
        if (preg_match('/Firefox/i', $userAgent)) {
            $webExplorer = 'Firefox';
        }
        if (preg_match('/Safari/i', $userAgent)) {
            $webExplorer = 'Safari';
        }
        if (preg_match('/Chrome/i', $userAgent)) {
            $webExplorer = 'Chrome';
        }

        return $webExplorer;
    }

    private function getVersion($userAgent, $webExplorer)
    {
        $version = null;

        if ($webExplorer !== 'Opera' && preg_match('#('.strtolower($webExplorer).')[/ ]?([0-9.]*)#', $userAgent, $match)) {
            $version = floor($match[2]);
        }
        if ($webExplorer == 'Opera' || $webExplorer == 'Safari' && preg_match('#(version)[/ ]?([0-9.]*)#', $userAgent, $match)) {
            $version = floor($match[2]);
        }

        return $version;
    }
}
