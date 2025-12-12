<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\MultimediaObject\Controller;

use App\ContentManagement\MultimediaObject\Application\BulkDelete\BulkDeleteMultimediaObjectRequest;
use App\ContentManagement\MultimediaObject\Application\BulkDelete\BulkDeleteMultimediaObjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkDeleteMultimediaObjectController extends AbstractController
{
    public function __construct(
        private BulkDeleteMultimediaObjectService $bulkDeleteMultimediaObjectService
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
            $dto = new BulkDeleteMultimediaObjectRequest($ids);
            $response = ($this->bulkDeleteMultimediaObjectService)($dto);

            if ($response->isFullySuccessful()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => sprintf(
                        'Successfully deleted %d multimedia objects',
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
                    'Deleted %d multimedia objects. Failed to delete %d.',
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
                'message' => 'An error occurred while deleting multimedia objects: '.$e->getMessage(),
            ], 500);
        }
    }
}
