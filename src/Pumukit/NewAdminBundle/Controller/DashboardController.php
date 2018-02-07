<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("is_granted('ROLE_ACCESS_DASHBOARD')")
 */
class DashboardController extends Controller implements NewAdminController
{
    /**
     * @Route("/dashboard")
     * @Route("/dashboard/default", name="pumukit_newadmin_dashboard_index_default")
     * @Template
     */
    public function indexAction(Request $request)
    {
    }

    /**
     * @Route("/dashboard/series/timeline.xml")
     */
    public function seriesTimelineAction(Request $request)
    {
        $repo = $this->get('doctrine_mongodb')->getManager()->getRepository('PumukitSchemaBundle:Series');
        $series = $repo->findAll();

        $XML = new \SimpleXMLElement('<data></data>');
        $XML->addAttribute('wiki-url', $request->getUri());
        $XML->addAttribute('wiki-section', 'Pumukit2 time-line Feed');

        foreach ($series as $s) {
            $XMLSeries = $XML->addChild('event', htmlspecialchars($s->getTitle()));
            $XMLSeries->addAttribute('start', $s->getPublicDate()->format("M j Y H:i:s \G\M\TP"));
            $XMLSeries->addAttribute('title', $s->getTitle());
            $XMLSeries->addAttribute('link', $this->get('router')->generate('pumukit_webtv_series_index', array('id' => $s->getId()), true));
        }

        return new Response($XML->asXML(), 200, array('Content-Type' => 'text/xml'));
    }
}
