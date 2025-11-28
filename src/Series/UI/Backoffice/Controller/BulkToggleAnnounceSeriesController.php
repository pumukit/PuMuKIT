<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\Controller;

use App\Series\Application\BulkToggleAnnounce\BulkToggleAnnounceSeriesRequest;
use App\Series\Application\BulkToggleAnnounce\BulkToggleAnnounceSeriesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkToggleAnnounceSeriesController extends AbstractController
{
    public function __construct(
        private BulkToggleAnnounceSeriesService $bulkToggleAnnounceSeriesService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No series IDs provided',
            ], 400);
        }

        try {
            $dto = new BulkToggleAnnounceSeriesRequest($ids);
            $response = ($this->bulkToggleAnnounceSeriesService)($dto);

            $statusCode = $response->isFullySuccessful() ? 200 : 207;

            return new JsonResponse($response->toArray(), $statusCode);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while toggling announce: '.$e->getMessage(),
            ], 500);
        }
    }
}
