<?php

namespace Pumukit\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LocaleController.
 */
class LocaleController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/locale/{locale}", name="pumukit_locale")
     *
     * @param string  $locale
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changeAction($locale, Request $request)
    {
        $this->get('session')->set('_locale', $locale);
        $this->get('router.request_context')->setParameter('_locale', $locale);
        $request->setLocale($locale);

        $referer = $request->headers->get('referer');
        if (!$referer) {
            return $this->redirect('/');
        }

        $paseReferer = parse_url($referer);
        $refererPath = $paseReferer['path'];
        $lastPath = str_replace($request->getBaseUrl(), '', $refererPath);

        try {
            $route = $this->get('router')->getMatcher()->match($lastPath);
        } catch (\Exception $e) {
            return $this->redirect('/');
        }

        if (!isset($route['_route'])) {
            return $this->redirect('/');
        }

        //array_filter ARRAY_FILTER_USE_BOTH only in 5.6
        $params = [];
        foreach ($route as $k => $v) {
            if ('_' != $k[0]) {
                $params[$k] = $v;
            }
        }
        $url = $this->generateUrl($route['_route'], $params);

        if (isset($paseReferer['query'])) {
            $url .= '?'.$paseReferer['query'];
        }

        if (isset($paseReferer['fragment'])) {
            $url .= '#'.$paseReferer['fragment'];
        }

        return $this->redirect($url);
    }
}
