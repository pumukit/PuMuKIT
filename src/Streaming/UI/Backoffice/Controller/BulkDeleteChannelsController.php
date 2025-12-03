<?php

declare(strict_types=1);

namespace App\Streaming\UI\Backoffice\Controller;

use App\Shared\Domain\LoggerInterface;
use App\Streaming\Application\Channel\BulkDelete\BulkDeleteChannelsRequest;
use App\Streaming\Application\Channel\BulkDelete\BulkDeleteChannelsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkDeleteChannelsController extends AbstractController
{
    public function __construct(
        private readonly BulkDeleteChannelsService $bulkDeleteChannelsService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        try {
            $dto = new BulkDeleteChannelsRequest($ids);
            $response = ($this->bulkDeleteChannelsService)($dto);

            $statusCode = $response->isFullySuccessful() ? 200 : 207;

            return new JsonResponse($response->toArray(), $statusCode);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Error inesperado en bulk delete channels controller', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Ocurrió un error al eliminar los canales',
            ], 500);
        }
    }
}

