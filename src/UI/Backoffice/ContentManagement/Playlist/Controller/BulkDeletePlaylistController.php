<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Playlist\Controller;

use App\ContentManagement\Playlist\Application\BulkDelete\BulkDeletePlaylistRequest;
use App\ContentManagement\Playlist\Application\BulkDelete\BulkDeletePlaylistService;
use App\Shared\Domain\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkDeletePlaylistController extends AbstractController
{
    public function __construct(
        private readonly BulkDeletePlaylistService $bulkDeletePlaylistService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        try {
            $dto = new BulkDeletePlaylistRequest($ids);
            $response = ($this->bulkDeletePlaylistService)($dto);

            $statusCode = $response->isFullySuccessful() ? 200 : 207;

            return new JsonResponse($response->toArray(), $statusCode);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in bulk delete playlist controller', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while deleting playlists',
            ], 500);
        }
    }
}
