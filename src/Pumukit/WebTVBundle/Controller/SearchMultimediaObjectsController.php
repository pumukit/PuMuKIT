<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Element;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;


class SearchMultimediaObjectsController extends Controller
{
    /**
     * @Route("/searchmultimediaobjects")
     * @Template()
     */
    public function indexAction()
    {
    	$repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
    	dump($repo);

		$series = $repo->findall();
		dump($series);

        return array('series' => $series);
    }
}