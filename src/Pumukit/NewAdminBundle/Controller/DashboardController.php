<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_DASHBOARD')")
 */
class DashboardController extends Controller implements NewAdminControllerInterface
{
    /**
     * @Route("/dashboard")
     * @Route("/dashboard/default", name="pumukit_newadmin_dashboard_index_default")
     * @Template
     */
    public function indexAction(Request $request)
    {
        $data = ['stats' => false];
        if ($request->get('show_stats')) {
            $dm = $this->get('doctrine_mongodb');

            $recordsService = $this->get('pumukitschema.stats');

            $groupBy = $request->get('group_by', 'year');

            $stats = $recordsService->getGlobalStats($groupBy);

            $data['stats'] = $stats;

            $storage = $this->get('pumukitencoder.profile')->getDirOutInfo();
            $data['storage'] = $storage;

            $seriesRepo = $dm->getRepository(Series::class);

            $data['num_series'] = $seriesRepo->count();
            $data['num_mm'] = array_sum(array_map(function ($e) {
                return $e['num'];
            }, $stats));
            $data['duration'] = array_sum(array_map(function ($e) {
                return $e['duration'];
            }, $stats));
            $data['size'] = array_sum(array_map(function ($e) {
                return $e['size'];
            }, $stats));
        }

        return $data;
    }

    /**
     * @Route("/dashboard/series/timeline.xml")
     */
    public function seriesTimelineAction(Request $request)
    {
        $repo = $this->get('doctrine_mongodb')->getManager()->getRepository(Series::class);
        $series = $repo->findAll();

        $XML = new \SimpleXMLElement('<data></data>');
        $XML->addAttribute('wiki-url', $request->getUri());
        $XML->addAttribute('wiki-section', 'Pumukit2 time-line Feed');

        foreach ($series as $s) {
            $XMLSeries = $XML->addChild('event', htmlspecialchars($s->getTitle()));
            $XMLSeries->addAttribute('start', $s->getPublicDate()->format('M j Y H:i:s \\G\\M\\TP'));
            $XMLSeries->addAttribute('title', $s->getTitle());
            $XMLSeries->addAttribute('link', $this->get('router')->generate('pumukit_webtv_series_index', ['id' => $s->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        return new Response($XML->asXML(), 200, ['Content-Type' => 'text/xml']);
    }
}
