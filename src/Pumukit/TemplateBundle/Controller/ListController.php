<?php

namespace Pumukit\TemplateBundle\Controller;

use Pumukit\TemplateBundle\Document\Template as PumukitTemplate;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ListController extends Controller
{
    /**
     * @Route("/t/{name}", name="pumukit_template")
     * @Template("PumukitTemplateBundle:List:index.html.twig")
     *
     * @param Request         $request
     * @param PumukitTemplate $template
     *
     * @return array
     */
    public function indexAction(Request $request, PumukitTemplate $template)
    {
        if ($template->isHide()) {
            throw $this->createNotFoundException('Page not found!');
        }

        $routeName = $request->get('_route');
        if ($request->get('_forwarded') && $request->get('_forwarded')->get('_route')) {
            $routeName = $request->get('_forwarded')->get('_route');
        }

        if (!$routeName) {
            $routeName = 'pumukit_webtv_index_index';
        }

        $this->get('pumukit_web_tv.breadcrumbs')->addList($template->getName(), $routeName, [], true);

        return ['template' => $template];
    }
}
