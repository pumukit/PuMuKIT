<?php

namespace Pumukit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

/**
 * Class LocaleController.
 */
class LocaleController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/locale/{locale}", name="pumukit_locale")
     */
    public function changeAction(Request $request, string $locale): RedirectResponse
    {
        /** @var SessionInterface */
        $session = $this->get('session');
        $session->set('_locale', $locale);

        /** @var RequestContext */
        $requestContext = $this->get('router.request_context');
        $requestContext->setParameter('_locale', $locale);

        $request->setLocale($locale);

        $referer = $request->headers->get('referer');
        if (!$referer) {
            return $this->redirect('/');
        }

        $paseReferer = parse_url($referer);

        if (!is_array($paseReferer)) {
            return $this->redirect('/');
        }

        if (!isset($paseReferer['path'])) {
            return $this->redirect('/');
        }

        $refererPath = $paseReferer['path'];
        $lastPath = str_replace($request->getBaseUrl(), '', $refererPath);

        try {
            /** @var Router */
            $router = $this->get('router');
            $route = $router->match($lastPath);
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
