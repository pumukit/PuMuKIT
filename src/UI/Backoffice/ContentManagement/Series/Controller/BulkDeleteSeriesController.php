<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Series\Controller;

use App\ContentManagement\Series\Application\BulkDelete\BulkDeleteSeriesRequest;
use App\ContentManagement\Series\Application\BulkDelete\BulkDeleteSeriesService;
use App\Shared\Domain\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkDeleteSeriesController extends AbstractController
{
    public function __construct(
        private readonly BulkDeleteSeriesService $bulkDeleteSeriesService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $ids = $data['ids'] ?? [];

        try {
            $dto = new BulkDeleteSeriesRequest($ids);
            $response = ($this->bulkDeleteSeriesService)($dto);

            $statusCode = $response->isFullySuccessful() ? 200 : 207;

            return new JsonResponse($response->toArray(), $statusCode);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in bulk delete series controller', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while deleting series',
            ], 500);
        }
    }
}
