<?php

declare(strict_types=1);

namespace App\Series\Application\BulkDelete;

use App\Series\Application\Delete\DeleteSeriesRequest;
use App\Series\Application\Delete\DeleteSeriesService;
use App\Series\Application\Find\FindSeriesRequest;
use App\Series\Application\Find\FindSeriesService;
use App\Series\Domain\Exception\SeriesNotFoundException;
use Psr\Log\LoggerInterface;

final class BulkDeleteSeriesService
{
    public function __construct(
        private FindSeriesService $findSeriesService,
        private DeleteSeriesService $deleteSeriesService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(BulkDeleteSeriesRequest $request): BulkDeleteSeriesResponse
    {
        BulkDeleteSeriesValidator::validate($request);

        $deletedCount = 0;
        $failedIds = [];
        $errors = [];

        foreach ($request->seriesIds as $seriesId) {
            try {
                $findRequest = new FindSeriesRequest($seriesId);
                $findResponse = ($this->findSeriesService)($findRequest);
                $series = $findResponse->series;

                if ($series->getMultimediaObjects()->count() > 0) {
                    $failedIds[] = $seriesId;
                    $errors[$seriesId] = sprintf(
                        'Series has %d multimedia objects. Delete them first.',
                        $series->getMultimediaObjects()->count()
                    );
                    $this->logger->warning('Cannot delete series with multimedia objects', [
                        'id' => $seriesId,
                        'multimedia_objects_count' => $series->getMultimediaObjects()->count()
                    ]);
                    continue;
                }

                $deleteRequest = new DeleteSeriesRequest($seriesId);
                ($this->deleteSeriesService)($deleteRequest);

                $deletedCount++;

                $this->logger->info('Series deleted successfully', [
                    'id' => $seriesId,
                    'title' => $series->getTitle()
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
                    'trace' => $e->getTraceAsString()
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
