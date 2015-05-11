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
    $baseUrl = $request->getBaseUrl();
    $lastPath = substr($referer, strpos($referer, $baseUrl) + strlen($baseUrl));
    $route = $this->get('router')->getMatcher()->match($lastPath);    
    //TODO
    dump($route);

    $url = $this->generateUrl($route["_route"], $route);
    return $this->redirect($url);
  }
}