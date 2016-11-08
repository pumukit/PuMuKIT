<?php

namespace Pumukit\Legacy\WebTVBundle\Controller;

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
   * @Route("/multimediaobjects/tag/{cod}", name="pumukit_webtv_bytag_multimediaobjects")
   * @Template("PumukitWebTVBundle:ByTag:index.html.twig")
   */
  public function multimediaObjectsAction(Tag $tag, Request $request)
  {
      $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
      $mmobjs = $repo->createBuilderWithTag($tag, array('record_date' => 1));
    
      $pagerfanta = $this->createPager($mmobjs, $request->query->get("page", 1));
      $this->updateBreadcrumbs($tag->getTitle(), "pumukit_webtv_bytag_multimediaobjects", array("cod" => $tag->getCod()));
    
      return array('title' => 'Multimedia objects with tag',
                 'objects' => $pagerfanta,
                 'tag' => $tag);
  }
  
  /**
   * @Route("/series/tag/{cod}", name="pumukit_webtv_bytag_series")
   * @Template("PumukitWebTVBundle:ByTag:index.html.twig")
   */
  public function seriesAction(Tag $tag, Request $request)
  {
      $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
      $series = $repo->createBuilderWithTag($tag, array('public_date' => +1));
    
      $pagerfanta = $this->createPager($series, $request->query->get("page", 1));
      $this->updateBreadcrumbs($tag->getTitle(), "pumukit_webtv_bytag_series", array("cod" => $tag->getCod()));
    
      return array('title' => 'Series with tag',
                 'objects' => $pagerfanta,
                 'tag' => $tag);
  }


    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
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
