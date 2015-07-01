<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\WebTVBundle\Controller\SearchController as Base;

class SearchController extends Base
{
    /**
     * @Route("/searchindex", name="pumukit_cmar_web_tv_search_index")
     * @Template("PumukitCmarWebTVBundle:Search:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $this->updateBreadcrumbs();
        $search = preg_replace('/[^a-z0-9_\.,;:_-]/i', ' ', $request->get('indexSearch'));

        return array('search' => $search);
    }

    protected function updateBreadcrumbs()
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList('Search', 'pumukit_cmar_web_tv_search_index', array(), true);
    }
}