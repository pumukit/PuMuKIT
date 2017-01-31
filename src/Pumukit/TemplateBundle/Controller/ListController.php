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
     * @Route("/t/{name}", name="pumukit_template")
     * @Template()
     */
    public function indexAction(PumukitTemplate $template, Request $request)
    {
        if ($template->isHide()) {
            throw $this->createNotFoundException('Page not found!');
        }

        $this->get('pumukit_web_tv.breadcrumbs')->addList($template->getName(), 'pumukit_webtv_index_index', array(), true);

        return array('template' => $template);
    }
}
