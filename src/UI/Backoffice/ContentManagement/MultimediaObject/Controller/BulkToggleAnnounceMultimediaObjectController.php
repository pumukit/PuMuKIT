<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\MultimediaObject\Controller;

use App\ContentManagement\MultimediaObject\Application\BulkToggleAnnounce\BulkToggleAnnounceMultimediaObjectRequest;
use App\ContentManagement\MultimediaObject\Application\BulkToggleAnnounce\BulkToggleAnnounceMultimediaObjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkToggleAnnounceMultimediaObjectController extends AbstractController
{
    public function __construct(
        private BulkToggleAnnounceMultimediaObjectService $bulkToggleAnnounceMultimediaObjectService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No multimedia object IDs provided',
            ], 400);
        }

        try {
            $dto = new BulkToggleAnnounceMultimediaObjectRequest($ids);
            $response = ($this->bulkToggleAnnounceMultimediaObjectService)($dto);

            if ($response->isFullySuccessful()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => sprintf(
                        'Successfully toggled announce for %d multimedia objects (%d published, %d hidden)',
                        $response->updatedCount,
                        $response->announcedCount,
                        $response->unAnnouncedCount
                    ),
                    'updated_count' => $response->updatedCount,
                    'announced_count' => $response->announcedCount,
                    'un_announced_count' => $response->unAnnouncedCount,
                ]);
            }

            $errorMessages = [];
            foreach ($response->errors as $id => $error) {
                $errorMessages[] = sprintf('ID %s: %s', $id, $error);
            }

            return new JsonResponse([
                'success' => $response->updatedCount > 0,
                'message' => sprintf(
                    'Toggled announce for %d multimedia objects (%d published, %d hidden). Failed for %d.',
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
                'message' => 'An error occurred while toggling announce: '.$e->getMessage(),
            ], 500);
        }
    }
}
