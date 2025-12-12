<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Series\Controller;

use App\ContentManagement\Series\Application\BulkToggleAnnounce\BulkToggleAnnounceSeriesRequest;
use App\ContentManagement\Series\Application\BulkToggleAnnounce\BulkToggleAnnounceSeriesService;
use App\Shared\Domain\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkToggleAnnounceSeriesController extends AbstractController
{
    public function __construct(
        private readonly BulkToggleAnnounceSeriesService $bulkToggleAnnounceSeriesService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $ids = $data['ids'] ?? [];

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
            $this->logger->error('Unexpected error in bulk toggle announce controller', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while toggling announce',
            ], 500);
        }
    }
}
