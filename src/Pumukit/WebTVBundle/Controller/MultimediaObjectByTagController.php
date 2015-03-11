<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Tag;

class MultimediaObjectByTagController extends Controller
{
    /**
     * @Route("/multimediaobjects/tag/{id}")
     * @Template()
     */
    public function indexAction(Tag $tag, Request $request)
    {
        $limit = 2;
        $page =  $request->get("page", 1);

    	$repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $mmobjs = $repo->createBuilderWithTag($tag, array('record_date' => 1));

        $adapter = new DoctrineODMMongoDBAdapter($mmobjs);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit); // 10 by default
        $pagerfanta->setCurrentPage($page); // 1 by default

        return array('mmobjs' => $pagerfanta, 'tag' => $tag);
    }
}