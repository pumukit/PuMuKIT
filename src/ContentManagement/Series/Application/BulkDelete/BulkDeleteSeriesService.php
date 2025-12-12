<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\BulkDelete;

use App\ContentManagement\Series\Application\Delete\DeleteSeriesRequest;
use App\ContentManagement\Series\Application\Delete\DeleteSeriesService;
use App\ContentManagement\Series\Domain\Exception\SeriesNotFoundException;
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
