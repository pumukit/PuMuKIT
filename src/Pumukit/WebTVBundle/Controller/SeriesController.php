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
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;

/**
 * Class SeriesController.
 */
class SeriesController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/series/{id}", name="pumukit_webtv_series_index")
     * @Template("PumukitWebTVBundle:Series:template.html.twig")
     *
     * @param Series  $series
     * @param Request $request
     *
     * @return array
     */
    public function indexAction(Series $series, Request $request)
    {
        $mmobjRepo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(MultimediaObject::class);

        $objects = $mmobjRepo->createBuilderWithSeriesAndStatus($series, [MultimediaObject::STATUS_PUBLISHED], ['rank' => 1]);

        $pagerfanta = $this->createPager($objects, $request->query->get('page', 1));

        $this->updateBreadcrumbs($series);

        return [
            'series' => $series,
            'multimediaObjects' => $pagerfanta,
        ];
    }

    /**
     * @Route("/series/magic/{secret}", name="pumukit_webtv_series_magicindex", defaults={"show_hide":true, "broadcast":false})
     * @Template("PumukitWebTVBundle:Series:template.html.twig")
     *
     * @param Series  $series
     * @param Request $request
     *
     * @return array
     */
    public function magicIndexAction(Series $series, Request $request)
    {
        $request->attributes->set('noindex', true);

        $mmobjRepo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(MultimediaObject::class);

        $objects = $mmobjRepo->createBuilderWithSeries($series, ['rank' => 1]);

        $pagerfanta = $this->createPager($objects, $request->query->get('page', 1));

        $this->updateBreadcrumbs($series);

        return [
            'series' => $series,
            'multimediaObjects' => $pagerfanta,
            'magic_url' => true,
        ];
    }

    /**
     * @param Series $series
     */
    private function updateBreadcrumbs(Series $series)
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addSeries($series);
    }

    /**
     * @param $objects
     * @param $page
     *
     * @return Pagerfanta
     */
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
