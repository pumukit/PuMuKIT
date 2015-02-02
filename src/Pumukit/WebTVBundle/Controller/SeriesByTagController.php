<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Tag;

class SeriesByTagController extends Controller
{
    /**
     * @Route("/series/tag/{id}/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date"})
     * @Template()
     */
    public function indexAction(Tag $tag,$sort, Request $request)
    {
    	$repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

        dump($tag);
        dump($request->getLocale());

        if($sort == "alphabetically"){
    		$series = $repo->findWithTag($tag, array('alphabetically.' . $request->getLocale() => +1));
        }
        else{
        	$series = $repo->findWithTag($tag, array('date' => +1));
        	dump($series);
        }

        dump($series);

        return array('series' => $series, 'sort' => $sort, 'tag' => $tag);
    }
}