<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

class TemplateController extends ContainerAware implements WebTVController
{
    public function templateAction($template, $title = null, $maxAge = null, $sharedAge = null, $private = null, Request $request = null)
    {
        $title = $this->container->get('translator')->trans($title);
        $this->container->get('pumukit_web_tv.breadcrumbs')->add($title, $request->get('_route'));

        /** @var $response \Symfony\Component\HttpFoundation\Response */
        $response = $this->container->get('templating')->renderResponse($template);

        if ($maxAge) {
            $response->setMaxAge($maxAge);
        }

        if ($sharedAge) {
            $response->setSharedMaxAge($sharedAge);
        }

        if ($private) {
            $response->setPrivate();
        } elseif ($private === false || (null === $private && ($maxAge || $sharedAge))) {
            $response->setPublic($private);
        }

        return $response;
    }
}
