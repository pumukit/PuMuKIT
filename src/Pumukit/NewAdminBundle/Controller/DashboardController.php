<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\StatsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_DASHBOARD')")
 */
class DashboardController extends AbstractController implements NewAdminControllerInterface
{
    /** @var DocumentManager */
    protected $documentManager;

    /** @var StatsService */
    protected $statsService;

    /** @var ProfileService */
    protected $profileService;

    /** @var RouterInterface */
    private $router;

    public function __construct(DocumentManager $documentManager, StatsService $statsService, ProfileService $profileService, RouterInterface $router)
    {
        $this->documentManager = $documentManager;
        $this->statsService = $statsService;
        $this->profileService = $profileService;
        $this->router = $router;
    }

    /**
     * @Route("/dashboard", name="pumukit_newadmin_dashboard_index")
     * @Route("/dashboard/default", name="pumukit_newadmin_dashboard_index_default")
     * @Template("@PumukitNewAdmin/Dashboard/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $data = ['stats' => false];
        if ($request->get('show_stats')) {
            $groupBy = $request->get('group_by', 'year');

            $stats = $this->statsService->getGlobalStats($groupBy);

            $data['stats'] = $stats;

            $storage = $this->profileService->getDirOutInfo();
            $data['storage'] = $storage;

            $seriesRepo = $this->documentManager->getRepository(Series::class);

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
        $repo = $this->documentManager->getRepository(Series::class);
        $series = $repo->findAll();

        $XML = new \SimpleXMLElement('<data></data>');
        $XML->addAttribute('wiki-url', $request->getUri());
        $XML->addAttribute('wiki-section', 'Pumukit time-line Feed');

        foreach ($series as $s) {
            $XMLSeries = $XML->addChild('event', htmlspecialchars($s->getTitle()));
            $XMLSeries->addAttribute('start', $s->getPublicDate()->format('M j Y H:i:s \\G\\M\\TP'));
            $XMLSeries->addAttribute('title', $s->getTitle());
            $XMLSeries->addAttribute('link', $this->router->generate('pumukit_webtv_series_index', ['id' => $s->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        return new Response($XML->asXML(), 200, ['Content-Type' => 'text/xml']);
    }
}
