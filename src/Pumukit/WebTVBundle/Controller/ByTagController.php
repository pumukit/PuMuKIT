<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Tag;

class ByTagController extends Controller
{

  private $limit = 10;

  /**
   * @Route("/multimediaobjects/tag/{cod}")
   * @Template("PumukitWebTVBundle:ByTag:index.html.twig")
   */
  public function multimediaObjectsAction(Tag $tag, Request $request)
  {
    $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
    $mmobjs = $repo->createBuilderWithTag($tag, array('record_date' => 1));
    
    $pagerfanta = $this->createPager($mmobjs, $request->query->get("page", 1));
    
    return array('title' => 'Multimedia objects with tag',
                 'objects' => $pagerfanta, 
                 'tag' => $tag);
  }
  
  /**
   * @Route("/series/tag/{cod}")
   * @Template("PumukitWebTVBundle:ByTag:index.html.twig")
   */
  public function seriesAction(Tag $tag, Request $request)
  {  
    $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
    $series = $repo->createBuilderWithTag($tag, array('public_date' => +1));
    
    $pagerfanta = $this->createPager($series, $request->query->get("page", 1));
    
    return array('title' => 'Series with tag',
                 'objects' => $pagerfanta,
                 'tag' => $tag);
  }



  private function createPager($objects, $page)
  {
    $adapter = new DoctrineODMMongoDBAdapter($objects);
    $pagerfanta = new Pagerfanta($adapter);
    $pagerfanta->setMaxPerPage($this->limit);
    $pagerfanta->setCurrentPage($page);    

    return $pagerfanta;
  }
}