<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class MediatecaController extends Controller
{
    /**
     * @Route("/mediateca/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date"})
     * @Template()
     */
    public function indexAction($sort, Request $request)
    {
    	$repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

        $sortField = "alphabetically" == $sort ? 'title.' . $request->getLocale() : "public_date";
        $series = $repo->findBy(array(), array($sortField => 1));        

        $this->get('pumukit_web_tv.breadcrumbs')->addList("All", "pumukit_webtv_mediateca_index");

        return array('series' => $series, 'sort' => $sort);
    }
}