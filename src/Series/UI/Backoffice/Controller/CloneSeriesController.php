<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\Controller;

use App\Series\Application\Clone\CloneSeriesRequest;
use App\Series\Application\Clone\CloneSeriesService;
use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Shared\Domain\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class CloneSeriesController extends AbstractController
{
    public function __construct(
        private readonly CloneSeriesService $cloneSeriesService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(string $id): JsonResponse|RedirectResponse
    {
        try {
            $request = new CloneSeriesRequest($id);
            $response = ($this->cloneSeriesService)($request);

            $this->addFlash('success', sprintf(
                'Series "%s" cloned successfully with %d multimedia objects',
                $response->clonedSeries->getTitle(),
                $response->multimediaObjectsCloned
            ));

            return $this->redirectToRoute('series_view', ['id' => $response->clonedSeries->getId()]);
        } catch (SeriesNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error cloning series', [
                'seriesId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json(['error' => 'An error occurred while cloning the series'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
