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

        dump($sort);
        dump($request->getLocale());

        if($sort == "alphabetically"){
    		$series = $repo->findBy(array(), array('alphabetically.' . $request->getLocale() => +1));
        }
        else{
        	$series = $repo->findBy(array(), array('date' => +1));
        	dump($series);
        }

        return array('series' => $series, 'sort' => $sort);
    }
}