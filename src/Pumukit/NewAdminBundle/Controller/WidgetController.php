<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class WidgetController extends Controller
{
    /**
     * @Template("PumukitNewAdminBundle:Widget:languageselect.html.twig")
     */
    public function languageselectAction()
    {
        $array_locales = $this->container->getParameter('pumukit.locales');
        if (count($array_locales) <= 1) {
            return new Response('');
        }

        return ['languages' => $array_locales];
    }
}
