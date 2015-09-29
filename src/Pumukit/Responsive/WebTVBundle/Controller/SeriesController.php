<?php

namespace Pumukit\Responsive\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class SeriesController extends Controller
{
    /**
     * @Route("/series/{id}", name="pumukit_responsive_webtv_series_index")
     * @Template("PumukitResponsiveWebTVBundle:Series:index.html.twig")
     */
    public function indexAction(Series $series, Request $request)
    {
        $mmobjRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $multimediaObjects = $mmobjRepo->findWithStatus($series, array(MultimediaObject::STATUS_PUBLISHED));

        $this->updateBreadcrumbs($series);

        return array('series' => $series,
                   'multimediaObjects' => $multimediaObjects, );
    }

    /**
     * @Route("/series/magic/{secret}", name="pumukit_responsive_webtv_series_magicindex", defaults={"filter": false})
     * @Template("PumukitResponsiveWebTVBundle:Series:index.html.twig")
     */
    public function magicIndexAction(Series $series, Request $request)
    {
        $mmobjRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $multimediaObjects = $mmobjRepo->findStandardBySeries($series);

        $this->updateBreadcrumbs($series);

        return array('series' => $series,
                   'multimediaObjects' => $multimediaObjects, );
    }

    private function updateBreadcrumbs(Series $series)
    {
        $breadcrumbs = $this->get('pumukit_responsive_web_tv.breadcrumbs');
        $breadcrumbs->addSeries($series);
    }
}
