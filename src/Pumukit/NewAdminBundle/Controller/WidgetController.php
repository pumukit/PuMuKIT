<?php

namespace Pumukit\NewAdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class WidgetController extends AbstractController
{
    private $locales;

    public function __construct(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * @Template("PumukitNewAdminBundle:Widget:languageselect.html.twig")
     */
    public function languageSelectAction()
    {
        if (count($this->locales) <= 1) {
            return new Response('');
        }

        return ['languages' => $this->locales];
    }
}
