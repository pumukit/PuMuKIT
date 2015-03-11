<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Tag;

class SeriesByTagController extends Controller
{
    /**
     * @Route("/series/tag/{id}")
     * @Template()
     */
    public function indexAction(Tag $tag, Request $request)
    {
        $limit = 1;
        $page =  $request->get("page", 1);

    	$repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $series = $repo->createBuilderWithTag($tag, array('public_date' => +1));

        $adapter = new DoctrineODMMongoDBAdapter($series);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return array('series' => $pagerfanta, 'tag' => $tag);
    }
}