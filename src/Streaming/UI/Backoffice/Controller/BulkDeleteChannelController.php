<?php

declare(strict_types=1);

namespace App\Streaming\UI\Backoffice\Controller;

use App\Shared\Domain\LoggerInterface;
use App\Streaming\Application\Channel\BulkDelete\BulkDeleteChannelRequest;
use App\Streaming\Application\Channel\BulkDelete\BulkDeleteChannelService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkDeleteChannelController extends AbstractController
{
    public function __construct(
        private readonly BulkDeleteChannelService $bulkDeleteChannelService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        try {
            $dto = new BulkDeleteChannelRequest($ids);
            $response = ($this->bulkDeleteChannelService)($dto);

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
