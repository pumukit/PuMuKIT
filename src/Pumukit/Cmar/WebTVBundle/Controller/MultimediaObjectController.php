<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Pumukit\WebTVBundle\Controller\MultimediaObjectController as Base;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;

class MultimediaObjectController extends Base
{
    public function preExecute(MultimediaObject $multimediaObject, Request $request)
    {
        if ($opencasturl = $multimediaObject->getProperty("opencasturl")) {
            $this->testBroadcast($multimediaObject, $request);
            $this->updateBreadcrumbs($multimediaObject);
            $this->incNumView($multimediaObject);
            $userAgent = $this->getRequest()->headers->get('user-agent');
            $isOldBrowser = $this->getIsOldBrowser($userAgent);
            return $this->render("PumukitCmarWebTVBundle:MultimediaObject:opencast.html.twig",
                                 array(
                                       "multimediaObject" => $multimediaObject,
                                       "is_old_browser" => $isOldBrowser
                                       )
                                 );
        }
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

        \phpCAS::forceAuthentication();

        if(!in_array(\phpCAS::getUser(), array($broadcast->getName(), "tv", "prueba", "adminmh", "admin", "sistemas.uvigo"))) {
          throw $this->createAccessDeniedException('Unable to access this page!');        
        }
      }
    }
}