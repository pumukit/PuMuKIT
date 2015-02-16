<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Element;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;


class SearchMultimediaObjectsController extends Controller
{
    /**
     * @Route("/searchmultimediaobjects")
     * @Template()
     */
    public function indexAction(Request $request)
    {
    	$limit = 2;
        $page =  $request->get("page", 1);

    	$repository_multimediaObjects = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
    	dump($repository_multimediaObjects);

    	$repository_tags = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
    	dump($repository_tags);

		$multimediaObjects = $repository_multimediaObjects->createBuilder();
		dump($multimediaObjects);

		$tags = $repository_tags->findall();
		dump($tags);

		$adapter = new DoctrineODMMongoDBAdapter($multimediaObjects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit); // 10 by default
        $pagerfanta->setCurrentPage($page); // 1 by default

        return array('multimediaObjects' => $pagerfanta, 'tags' => $tags);
    }
}