<?php

declare(strict_types=1);

namespace App\MultimediaObject\UI\Backoffice\Controller;

use App\MultimediaObject\Application\BulkDelete\BulkDeleteMultimediaObjectsRequest;
use App\MultimediaObject\Application\BulkDelete\BulkDeleteMultimediaObjectsService;
use App\Shared\Domain\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkDeleteMultimediaObjectsController extends AbstractController
{
    public function __construct(
        private readonly BulkDeleteMultimediaObjectsService $bulkDeleteMultimediaObjectsService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        try {
            $dto = new BulkDeleteMultimediaObjectsRequest($ids);
            $response = ($this->bulkDeleteMultimediaObjectsService)($dto);

            $statusCode = $response->isFullySuccessful() ? 200 : 207;

            return new JsonResponse($response->toArray(), $statusCode);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in bulk delete multimedia objects controller', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while deleting multimedia objects',
            ], 500);
        }
    }
}

