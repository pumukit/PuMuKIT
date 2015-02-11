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
        $limit = 4;
        $page =  $request->get("page", 1);

        dump($page);

    	$repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        dump($tag);
        dump($request->getLocale());

        /*if($sort == "alphabetically"){
    		$mmobjs = $repo->findWithTag($tag, array('alphabetically.' . $request->getLocale() => +1));
        }
        else{*/
        	$mmobjs = $repo->createBuilderWithTag($tag, array('record_date' => 1));
        	dump($mmobjs);
       // }

        $adapter = new DoctrineODMMongoDBAdapter($mmobjs);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit); // 10 by default
        $pagerfanta->setCurrentPage($page); // 1 by default

        dump($pagerfanta);

        return array('mmobjs' => $pagerfanta, 'tag' => $tag);
    }
}