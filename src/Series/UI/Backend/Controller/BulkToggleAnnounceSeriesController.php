<?php

declare(strict_types=1);

namespace App\Series\UI\Backend\Controller;

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
                'message' => 'No series IDs provided'
            ], 400);
        }

        try {
            $dto = new BulkToggleAnnounceSeriesRequest($ids);
            $response = ($this->bulkToggleAnnounceSeriesService)($dto);

            if ($response->isFullySuccessful()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => sprintf(
                        'Successfully toggled announce for %d series (%d announced, %d not announced)',
                        $response->updatedCount,
                        $response->announcedCount,
                        $response->unAnnouncedCount
                    ),
                    'updated_count' => $response->updatedCount,
                    'announced_count' => $response->announcedCount,
                    'un_announced_count' => $response->unAnnouncedCount
                ]);
            }

            $errorMessages = [];
            foreach ($response->errors as $id => $error) {
                $errorMessages[] = sprintf('ID %s: %s', $id, $error);
            }

            return new JsonResponse([
                'success' => $response->updatedCount > 0,
                'message' => sprintf(
                    'Toggled announce for %d series (%d announced, %d not announced). Failed for %d series.',
                    $response->updatedCount,
                    $response->announcedCount,
                    $response->unAnnouncedCount,
                    count($response->failedIds)
                ),
                'updated_count' => $response->updatedCount,
                'announced_count' => $response->announcedCount,
                'un_announced_count' => $response->unAnnouncedCount,
                'failed_count' => count($response->failedIds),
                'failed_ids' => $response->failedIds,
                'errors' => $errorMessages
            ], 207);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while toggling announce: ' . $e->getMessage()
            ], 500);
        }
    }
}

