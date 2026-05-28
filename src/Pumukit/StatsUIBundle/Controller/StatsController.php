<?php

declare(strict_types=1);

namespace Pumukit\StatsUIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/admin/stats")
 */
#[IsGranted('ROLE_ACCESS_STATS')]
class StatsController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/series", name="pumukit_stats_series_index")
     * @Route("/objects", name="pumukit_stats_mmobj_index")
     * @Route("/series/{id}", name="pumukit_stats_series_index_id")
     * @Route("/objects/{id}", name="pumukit_stats_mmobj_index_id")
     */
    public function indexAction(Request $request): Response
    {
        $routeName = $request->get('_route');

        if ('pumukit_stats_series_index' === $routeName) {
            $title = 'General Statistics for Series';
        } elseif ('pumukit_stats_mmobj_index' === $routeName) {
            $title = 'General Statistics for Objects (videos/audios)';
        } elseif ('pumukit_stats_series_index_id' === $routeName) {
            $title = 'Statistics of the series';
        } else {
            $title = 'Statistics of the object';
        }

        return $this->render('@PumukitStatsUI/Stats/index.html.twig', [
            'template_title' => $this->translator->trans($title),
        ]);
    }
}
