<?php

namespace Pumukit\Legacy\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class SeriesController extends Controller
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
        $multimediaObjects = $mmobjRepo->findWithStatus($series, array(MultimediaObject::STATUS_PUBLISHED));

        $this->updateBreadcrumbs($series);
        
        return array('series' => $series,
                   'multimediaObjects' => $multimediaObjects, );
    }

    /**
     * @Route("/series/magic/{secret}", name="pumukit_webtv_series_magicindex", defaults={"show_hide":true, "broadcast":false, "track":false})
     * @Template("PumukitWebTVBundle:Series:index.html.twig")
     */
    public function magicIndexAction(Series $series, Request $request)
    {
        $mmobjRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $multimediaObjects = $mmobjRepo->findStandardBySeries($series);

        $this->updateBreadcrumbs($series);

        return array('series' => $series,
                   'multimediaObjects' => $multimediaObjects,
                   'magic_url' => true, );
    }

    private function updateBreadcrumbs(Series $series)
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addSeries($series);
    }
}
