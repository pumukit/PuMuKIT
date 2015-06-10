<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Pumukit\WebTVBundle\Controller\MultimediaObjectController as Base;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectController extends Base
{
    public function preExecute(MultimediaObject $multimediaObject, Request $request)
    {
        if ($opencasturl = $multimediaObject->getProperty("opencasturl")) {
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
}