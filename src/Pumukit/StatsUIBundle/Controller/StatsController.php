<?php

namespace Pumukit\StatsUIBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/stats")
 * @Security("is_granted('ROLE_ACCESS_STATS')")
 */
class StatsController extends AbstractController
{
    /**
     * @Route("/series", name="pumukit_stats_series_index")
     * @Route("/objects", name="pumukit_stats_mmobj_index")
     * @Route("/series/{id}", name="pumukit_stats_series_index_id")
     * @Route("/objects/{id}", name="pumukit_stats_mmobj_index_id")
     * @Template("PumukitStatsUIBundle:Stats:index.html.twig")
     */
    public function indexAction(): array
    {
        return [];
    }
}
