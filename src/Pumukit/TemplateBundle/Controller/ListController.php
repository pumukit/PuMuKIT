<?php

namespace Pumukit\TemplateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\TemplateBundle\Document\Template as PumukitTemplate;

class ListController extends Controller
{
    /**
     * @Route("/t/{name}")
     * @Template()
     */
    public function indexAction(PumukitTemplate $template, Request $request)
    {
        if ($template->isHide()) {
            throw $this->createNotFoundException('Page not found!');
        }

        return array('template' => $template);
    }
}
