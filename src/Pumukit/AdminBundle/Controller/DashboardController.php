<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DashboardController extends Controller
{
    /**
     *
     * @Route("/dashboard")
     * @Template
     */
    public function indexAction(Request $request)
    {
    }

    /**
     *
     * @Route("/dashboard/series/timeline.xml")
     */
    public function seriesTimelineAction(Request $request)
    {
        $repo = $this->get('doctrine_mongodb')->getManager()->getRepository('PumukitSchemaBundle:Series');
        $series = $repo->findAll();

        $XML = new \SimpleXMLElement("<data></data>");
        $XML->addAttribute('wiki-url', 'TODO');
        $XML->addAttribute('wiki-section', 'TODO');

        foreach($series as $s) {
          $XMLSeries = $XML->addChild('event', $s->getTitle());
          $XMLSeries->addAttribute('start', $s->getPublicDate()->format("M j Y H:i:s \G\M\TP"));
          $XMLSeries->addAttribute('title', $s->getTitle());
          $XMLSeries->addAttribute('link', 'TODO');
        }

        return new Response($XML->asXML(), 200, array('Content-Type' => 'text/xml'));
    }
}
