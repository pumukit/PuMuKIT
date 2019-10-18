<?php

namespace Pumukit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;

class LocaleController extends AbstractController implements WebTVControllerInterface
{
    /**
     * @Route("/locale/{locale}", name="pumukit_locale")
     */
    public function changeAction(Request $request, string $locale): RedirectResponse
    {
        /** @var SessionInterface */
        $session = $this->get('session');
        $session->set('_locale', $locale);

        $request->setLocale($locale);

        $referer = $request->headers->get('referer');
        if (!$referer) {
            return $this->redirect('/');
        }

        $parseReferer = parse_url($referer);

        if (!is_array($parseReferer)) {
            return $this->redirect('/');
        }

        if (!isset($parseReferer['path'])) {
            return $this->redirect('/');
        }

        $refererPath = $parseReferer['path'];
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

        $params = [];
        foreach ($route as $k => $v) {
            if ('_' !== $k[0]) {
                $params[$k] = $v;
            }
        }
        $url = $this->generateUrl($route['_route'], $params);

        if (isset($parseReferer['query'])) {
            $url .= '?'.$parseReferer['query'];
        }

        if (isset($parseReferer['fragment'])) {
            $url .= '#'.$parseReferer['fragment'];
        }

        return $this->redirect($url);
    }
}
