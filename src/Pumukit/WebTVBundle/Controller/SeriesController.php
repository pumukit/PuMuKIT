<?php

namespace Pumukit\WebTVBundle\Controller;

use Pagerfanta\Pagerfanta;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
     * @throws \Exception
     *
     * @return array
     */
    public function indexAction(Series $series, Request $request)
    {
        $mmobjRepo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository(MultimediaObject::class);

        $objects = $mmobjRepo->createBuilderWithSeriesAndStatus($series, [MultimediaObject::STATUS_PUBLISHED], ['rank' => 1]);

        $pager = $this->createPager($objects, $request->query->get('page', 1));

        $this->updateBreadcrumbs($series);

        return [
            'series' => $series,
            'multimediaObjects' => $pager,
        ];
    }

    /**
     * @Route("/series/magic/{secret}", name="pumukit_webtv_series_magicindex", defaults={"show_hide":true, "broadcast":false})
     * @Template("PumukitWebTVBundle:Series:template.html.twig")
     *
     * @param Series  $series
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return array
     */
    public function magicIndexAction(Series $series, Request $request)
    {
        $request->attributes->set('noindex', true);

        $mmobjRepo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository(MultimediaObject::class);

        $objects = $mmobjRepo->createBuilderWithSeries($series, ['rank' => 1]);

        $pager = $this->createPager($objects, $request->query->get('page', 1));

        $this->updateBreadcrumbs($series);

        return [
            'series' => $series,
            'multimediaObjects' => $pager,
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
     * @throws \Exception
     *
     * @return mixed|Pagerfanta
     */
    private function createPager($objects, $page)
    {
        $limit = $this->container->getParameter('limit_objs_series');

        return $this->get('pumukit_web_tv.pagination_service')->createDoctrineODMMongoDBAdapter($objects, $page, $limit);
    }
}
