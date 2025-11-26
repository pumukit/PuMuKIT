<?php

declare(strict_types=1);

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\BulkDelete\BulkDeleteSeriesRequest;
use App\Series\Application\BulkDelete\BulkDeleteSeriesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkDeleteSeriesController extends AbstractController
{
    public function __construct(
        private BulkDeleteSeriesService $bulkDeleteSeriesService
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
            $dto = new BulkDeleteSeriesRequest($ids);
            $response = ($this->bulkDeleteSeriesService)($dto);

            if ($response->isFullySuccessful()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => sprintf(
                        'Successfully deleted %d series',
                        $response->deletedCount
                    ),
                    'deleted_count' => $response->deletedCount,
                ]);
            }

            $errorMessages = [];
            foreach ($response->errors as $id => $error) {
                $errorMessages[] = sprintf('ID %s: %s', $id, $error);
            }

            return new JsonResponse([
                'success' => $response->deletedCount > 0,
                'message' => sprintf(
                    'Deleted %d series. Failed to delete %d series.',
                    $response->deletedCount,
                    count($response->failedIds)
                ),
                'deleted_count' => $response->deletedCount,
                'failed_count' => count($response->failedIds),
                'failed_ids' => $response->failedIds,
                'errors' => $errorMessages,
            ], 207);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while deleting series: '.$e->getMessage(),
            ], 500);
        }
    }
}
