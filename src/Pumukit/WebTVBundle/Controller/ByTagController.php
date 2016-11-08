<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Tag;

class ByTagController extends Controller implements WebTVController
{

  /**
   * @Route("/multimediaobjects/tag/{tagCod}", name="pumukit_webtv_bytag_multimediaobjects", defaults={"tagCod": null})
   * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
   * @Template("PumukitWebTVBundle:ByTag:index.html.twig")
   */
  public function multimediaObjectsAction(Tag $tag, Request $request)
  {
      $numberCols = $this->container->getParameter('columns_objs_bytag');
      $limit = $this->container->getParameter('limit_objs_bytag');

      $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

      if ($request->get('useTagAsGeneral')) {
          //This should be included on SchemaBundle:MultimediaObjectRepository.
        $mmobjs = $repo->createBuilderWithGeneralTag($tag, array('record_date' => -1));
          $title = $this->get('translator')->trans("General %title%", array('%title%' => $tag->getTitle()));
          $this->updateBreadcrumbs($title, 'pumukit_webtv_bytag_multimediaobjects', array('cod' => $tag->getCod(), 'useTagAsGeneral' => true));
      } else {
          $mmobjs = $repo->createBuilderWithTag($tag, array('record_date' => -1));
          $this->updateBreadcrumbs($tag->getTitle(), 'pumukit_webtv_bytag_multimediaobjects', array('cod' => $tag->getCod()));
          $title = $tag->getTitle();
      }

      $pagerfanta = $this->createPager($mmobjs, $request->query->get('page', 1), $limit);

      $title = $this->get('translator')->trans('Multimedia objects with tag: %title%', array('%title%' => $title));
      return array('title' => $title,
                 'objects' => $pagerfanta,
                 'tag' => $tag,
                 'number_cols' => $numberCols);
  }

  /**
   * @Route("/series/tag/{tagCod}",  name="pumukit_webtv_bytag_series", defaults={"tagCod": null})
   * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
   * @Template("PumukitWebTVBundle:ByTag:index.html.twig")
   */
  public function seriesAction(Tag $tag, Request $request)
  {
      $numberCols = $this->container->getParameter('columns_objs_bytag');
      $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
      $series = $repo->createBuilderWithTag($tag, array('public_date' => -1));

      $pagerfanta = $this->createPager($series, $request->query->get('page', 1));
      $this->updateBreadcrumbs($tag->getTitle(), 'pumukit_webtv_bytag_series', array('cod' => $tag->getCod()));

      $title = $tag->getTitle();
      $title = $this->get('translator')->trans('Series with tag: %title%', array('%title%' => $title));

      return array('title' => $title,
                 'objects' => $pagerfanta,
                 'tag' => $tag,
                 'number_cols' => $numberCols);
  }

    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->add($title, $routeName, $routeParameters);
    }

    private function createPager($objects, $page, $limit = 10)
    {
        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }
}
