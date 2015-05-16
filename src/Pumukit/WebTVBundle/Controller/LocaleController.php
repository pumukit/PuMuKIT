<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class LocaleController extends Controller
{
  /**
   * @Route("/locale/{locale}", name="pumukit_locale")
   */
  public function changeAction($locale, Request $request)
  {
    //TODO validate if is a valid locale using conf file.
    $this->get('session')->set('_locale', $locale);
    
    $referer = $request->headers->get("referer");
    $base = $request->getUriForPath("");
    $lastPath = str_replace($base, "", $referer);
    $route = $this->get('router')->getMatcher()->match($lastPath);    

    //array_filter ARRAY_FILTER_USE_BOTH only in 5.6
    $params = array();
    foreach($route as $k => $v) {
      if ("_" != $k[0]) {
        $params[$k] = $v;
      }
    }

    $url = $this->generateUrl($route["_route"], $params);
    return $this->redirect($url);
  }
}