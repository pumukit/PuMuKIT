<?php

namespace Pumukit\FutureWebTVBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TemplateController.
 */
class TemplateController implements WebTVControllerInterface
{
    use ContainerAwareTrait;

    /**
     * @param              $template
     * @param null         $title
     * @param null         $maxAge
     * @param null         $sharedAge
     * @param null         $private
     * @param Request|null $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Twig\Error\Error
     */
    public function templateAction($template, $title = null, $maxAge = null, $sharedAge = null, $private = null, Request $request = null)
    {
        $title = $this->container->get('translator')->trans($title);
        $this->container->get('pumukit_web_tv.breadcrumbs')->add($title, $request->get('_route'));

        $response = $this->container->get('templating')->renderResponse($template);

        if ($maxAge) {
            $response->setMaxAge($maxAge);
        }

        if ($sharedAge) {
            $response->setSharedMaxAge($sharedAge);
        }

        if ($private) {
            $response->setPrivate();
        } elseif (false === $private || (null === $private && ($maxAge || $sharedAge))) {
            $response->setPublic($private);
        }

        return $response;
    }
}
