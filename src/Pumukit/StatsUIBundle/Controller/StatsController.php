<?php

namespace Pumukit\StatsUIBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/stats")
 * @Security("is_granted('ROLE_ACCESS_STATS')")
 */
class StatsController extends Controller
{
    /**
     * @Route("/series", name="pumukit_stats_series_index")
     * @Route("/objects", name="pumukit_stats_mmobj_index")
     * @Route("/series/{id}", name="pumukit_stats_series_index_id")
     * @Route("/objects/{id}", name="pumukit_stats_mmobj_index_id")
     * @Template("PumukitStatsUIBundle:Stats:index.html.twig")
     */
    public function indexAction(Request $request)
    {
    }
}
