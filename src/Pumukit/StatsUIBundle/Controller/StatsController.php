<?php

declare(strict_types=1);

namespace Pumukit\StatsUIBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\StatsBundle\Services\StatsScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/stats')]
#[IsGranted('ROLE_ACCESS_STATS')]
class StatsController extends AbstractController
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly StatsScope $scope,
    ) {}

    #[Route('', name: 'pumukit_stats_index')]
    public function indexAction(): Response
    {
        return $this->render('@PumukitStatsUI/Stats/index.html.twig', [
            'mode' => 'overview',
            'subject' => null,
        ]);
    }

    #[Route('/series/{id}', name: 'pumukit_stats_series_index_id')]
    public function seriesAction(string $id): Response
    {
        $series = preg_match('/^[0-9a-f]{24}$/', $id) ? $this->documentManager->getRepository(Series::class)->find($id) : null;
        if (!$series instanceof Series || !$this->scope->canSeeSeries($id)) {
            throw $this->createNotFoundException();
        }

        return $this->render('@PumukitStatsUI/Stats/index.html.twig', [
            'mode' => 'series',
            'subject' => $series,
        ]);
    }

    #[Route('/objects/{id}', name: 'pumukit_stats_mmobj_index_id')]
    public function multimediaObjectAction(string $id): Response
    {
        $multimediaObject = preg_match('/^[0-9a-f]{24}$/', $id) ? $this->documentManager->getRepository(MultimediaObject::class)->find($id) : null;
        if (!$multimediaObject instanceof MultimediaObject || !$this->scope->canSeeMultimediaObject($id)) {
            throw $this->createNotFoundException();
        }

        return $this->render('@PumukitStatsUI/Stats/index.html.twig', [
            'mode' => 'multimediaObject',
            'subject' => $multimediaObject,
        ]);
    }
}
