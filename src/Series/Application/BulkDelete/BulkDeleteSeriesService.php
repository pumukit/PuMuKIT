<?php

declare(strict_types=1);

namespace App\Series\Application\BulkDelete;

use App\Series\Application\Delete\DeleteSeriesRequest;
use App\Series\Application\Delete\DeleteSeriesService;
use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Shared\Domain\LoggerInterface;

final class BulkDeleteSeriesService
{
    public function __construct(
        private DeleteSeriesService $deleteSeriesService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(BulkDeleteSeriesRequest $request): BulkDeleteSeriesResponse
    {
        $deletedCount = 0;
        $failedIds = [];
        $errors = [];

        foreach ($request->seriesIds as $seriesId) {
            try {
                // Just try to delete directly - DeleteSeriesService will handle MM objects
                $deleteRequest = new DeleteSeriesRequest($seriesId);
                $deleteResponse = ($this->deleteSeriesService)($deleteRequest);

                if (!$deleteResponse->success) {
                    $failedIds[] = $seriesId;
                    $errors[$seriesId] = $deleteResponse->message;
                    $this->logger->warning('Failed to delete series', [
                        'id' => $seriesId,
                        'reason' => $deleteResponse->message,
                    ]);

                    continue;
                }

                ++$deletedCount;

                $this->logger->info('Series deleted successfully', [
                    'id' => $seriesId,
                    'message' => $deleteResponse->message,
                ]);
            } catch (SeriesNotFoundException $e) {
                $failedIds[] = $seriesId;
                $errors[$seriesId] = 'Series not found';
                $this->logger->warning('Series not found for deletion', ['id' => $seriesId]);
            } catch (\Exception $e) {
                $failedIds[] = $seriesId;
                $errors[$seriesId] = $e->getMessage();
                $this->logger->error('Error deleting series', [
                    'id' => $seriesId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return new BulkDeleteSeriesResponse(
            deletedCount: $deletedCount,
            failedIds: $failedIds,
            errors: $errors
        );
    }
}
