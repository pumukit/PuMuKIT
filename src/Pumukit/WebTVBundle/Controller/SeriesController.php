<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class SeriesController extends Controller implements WebTVController
{
    /**
     * @Route("/series/{id}", name="pumukit_webtv_series_index")
     * @Template("PumukitWebTVBundle:Series:index.html.twig")
     */
    public function indexAction(Series $series, Request $request)
    {
        $mmobjRepo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject');

        $objects = $mmobjRepo->createBuilderWithSeriesAndStatus($series, array(MultimediaObject::STATUS_PUBLISHED), array('rank' => 1));

        $pagerfanta = $this->createPager($objects, $request->query->get('page', 1));

        $this->updateBreadcrumbs($series);

        return array(
            'series' => $series,
            'multimediaObjects' => $pagerfanta,
        );
    }

    /**
     * @Route("/series/magic/{secret}", name="pumukit_webtv_series_magicindex", defaults={"show_hide":true, "broadcast":false, "track":false})
     * @Template("PumukitWebTVBundle:Series:index.html.twig")
     */
    public function magicIndexAction(Series $series, Request $request)
    {
        $request->attributes->set('noindex', true);

        $mmobjRepo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject');

        $objects = $mmobjRepo->createBuilderWithSeries($series, array('rank' => 1));

        $pagerfanta = $this->createPager($objects, $request->query->get('page', 1));

        $this->updateBreadcrumbs($series);

        return array(
            'series' => $series,
            'multimediaObjects' => $pagerfanta,
            'magic_url' => true,
        );
    }

    private function updateBreadcrumbs(Series $series)
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addSeries($series);
    }

    private function createPager($objects, $page)
    {
        $limit = $this->container->getParameter('limit_objs_series');

        if (0 == $limit) {
            return $objects->getQuery()->execute();
        }
        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }
}
