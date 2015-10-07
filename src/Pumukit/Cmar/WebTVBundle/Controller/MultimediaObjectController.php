<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\WebTVBundle\Controller\MultimediaObjectController as Base;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;

class MultimediaObjectController extends Base
{
    public function preExecute(MultimediaObject $multimediaObject, Request $request)
    {
        if ($opencasturl = $multimediaObject->getProperty("opencasturl")) {
            $response = $this->testBroadcast($multimediaObject, $request);
            if($response instanceof Response) {
                return $response;
            }

            $this->updateBreadcrumbs($multimediaObject);
            $this->incNumView($multimediaObject);
            $this->dispatch($multimediaObject);
            $userAgent = $this->getRequest()->headers->get('user-agent');
            $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
            $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
            $isOldBrowser = $this->getIsOldBrowser($userAgent);

            return $this->render("PumukitCmarWebTVBundle:MultimediaObject:opencast.html.twig",
                                 array(
                                       "multimediaObject" => $multimediaObject,
                                       "is_old_browser" => $isOldBrowser,
                                       "mobile_device" => $mobileDevice
                                       )
                                 );
        }
    }

   /**
    * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe")
    * @Template()
    */
    public function iframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        $response = $this->testBroadcast($multimediaObject, $request);
        if($response instanceof Response) {
            return $response;
        }

        if ($multimediaObject->getProperty('opencast')) {
            $this->updateBreadcrumbs($multimediaObject);
            $this->incNumView($multimediaObject);
            $this->dispatch($multimediaObject);
            $userAgent = $this->getRequest()->headers->get('user-agent');
            $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
            $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
            $isOldBrowser = $this->getIsOldBrowser($userAgent);
            $track = $multimediaObject->getTrackWithTag('sbs');

            return $this->render("PumukitCmarWebTVBundle:MultimediaObject:opencastiframe.html.twig",
                                 array(
                                       "multimediaObject" => $multimediaObject,
                                       "track" => $track,
                                       "is_old_browser" => $isOldBrowser,
                                       "mobile_device" => $mobileDevice
                                       )
                                 );
        }

        $track = $request->query->has('track_id') ?
          $multimediaObject->getTrackById($request->query->get('track_id')) :
          $multimediaObject->getFilteredTrackWithTags(array('display'));

        if (!$track)
            throw $this->createNotFoundException();

        $this->incNumView($multimediaObject, $track);
        $this->dispatch($multimediaObject, $track);

        return array('autostart' => $request->query->get('autostart', 'true'),
                     'intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'track' => $track);
    }

    private function getIsOldBrowser($userAgent)
    {
        $isOldBrowser = false;
        $webExplorer = $this->getWebExplorer($userAgent);
        $version = $this->getVersion($userAgent, $webExplorer);
        if (($webExplorer == 'IE') || ($webExplorer == 'MSIE') || $webExplorer == 'Firefox' || $webExplorer == 'Opera' || ($webExplorer == 'Safari' && $version<4)){
            $isOldBrowser = true;
        }

        return $isOldBrowser;
    }

    private function getWebExplorer($userAgent)
    {
        if (preg_match('/MSIE/i', $userAgent))         $webExplorer = "MSIE";
        if (preg_match('/Opera/i', $userAgent))        $webExplorer = 'Opera';
        if (preg_match('/Firefox/i', $userAgent))      $webExplorer = 'Firefox';
        if (preg_match('/Safari/i', $userAgent))       $webExplorer = 'Safari';
        if (preg_match('/Chrome/i', $userAgent))       $webExplorer = 'Chrome';

        return $webExplorer;
    }

    private function getVersion($userAgent, $webExplorer)
    {
        $version = null;

        if($webExplorer!=='Opera' && preg_match("#(".strtolower($webExplorer).")[/ ]?([0-9.]*)#", $userAgent, $match))
            $version = floor($match[2]);
        if($webExplorer=='Opera' || $webExplorer=='Safari' && preg_match("#(version)[/ ]?([0-9.]*)#", $userAgent, $match))
            $version = floor($match[2]);

        return $version;
    }


   public function testBroadcast(MultimediaObject $multimediaObject, Request $request)
   {
      if (($broadcast = $multimediaObject->getBroadcast()) && 
          (Broadcast::BROADCAST_TYPE_PUB !== $broadcast->getBroadcastTypeId())) {

          if ((!$this->container->hasParameter('pumukit_cmar_web_tv.cas_url')) && 
              (!$this->container->hasParameter('pumukit_cmar_web_tv.cas_port')) &&
              (!$this->container->hasParameter('pumukit_cmar_web_tv.cas_uri')) &&
              (!$this->container->hasParameter('pumukit_cmar_web_tv.cas_allowed_ip_clients'))) {
              throw $this->createNotFoundException('PumukitCmarWebTVBundle not configured.');
          }

          $casService = $this->get('pumukit_cmar_web_tv.casservice');
          $casService->forceAuthentication();

          if(!in_array($casService->getUser(), array($broadcast->getName(), "tv", "prueba", "adminmh", "admin", "sistemas.uvigo"))) {
              return new Response($this->renderView("PumukitWebTVBundle:Index:401unauthorized.html.twig", array()), 401);
          }
      }
      if ($broadcast && (Broadcast::BROADCAST_TYPE_PRI === $broadcast->getBroadcastTypeId()))
        return new Response($this->renderView("PumukitWebTVBundle:Index:403forbidden.html.twig", array()), 403);

      return true;
    }

   /**
     * @Route("/mmobj/iframe/{id}", name="pumukit_webtv_multimediaobject_mmobjiframe")
     * @Template()
     */
    public function mmobjiframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        return array('mm' => $multimediaObject);
    }
}