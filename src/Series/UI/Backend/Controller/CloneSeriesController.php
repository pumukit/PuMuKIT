<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\Clone\CloneSeriesService;
use App\Series\Domain\Exception\SeriesNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class CloneSeriesController extends AbstractController
{
    public function __invoke(CloneSeriesService $cloneSeriesService, string $id): RedirectResponse|JsonResponse
    {
        try {
            $clonedSeries = ($cloneSeriesService)($id);

            return $this->redirectToRoute('series_view', ['id' => $clonedSeries->getId()]);
        } catch (SeriesNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}

