<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\BulkToggleAnnounce;

use App\ContentManagement\Series\Application\Find\FindSeriesRequest;
use App\ContentManagement\Series\Application\Find\FindSeriesService;
use App\ContentManagement\Series\Domain\Event\SeriesUpdatedEvent;
use App\ContentManagement\Series\Domain\Exception\SeriesNotFoundException;
use App\ContentManagement\Series\Domain\Repository\SeriesRepositoryInterface;
use App\Shared\Domain\EventBusInterface;
use App\Shared\Domain\LoggerInterface;

final class BulkToggleAnnounceSeriesService
{
    public function __construct(
        private readonly FindSeriesService $findSeriesService,
        private readonly SeriesRepositoryInterface $seriesRepository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(BulkToggleAnnounceSeriesRequest $request): BulkToggleAnnounceSeriesResponse
    {
        BulkToggleAnnounceSeriesValidator::validate($request);

        $updatedCount = 0;
        $announcedCount = 0;
        $unAnnouncedCount = 0;
        $failedIds = [];
        $errors = [];

        foreach ($request->seriesIds as $seriesId) {
            try {
                $findRequest = new FindSeriesRequest($seriesId);
                $findResponse = ($this->findSeriesService)($findRequest);
                $series = $findResponse->series;

                $wasAnnounced = $series->getAnnounce();
                $series->setAnnounce(!$wasAnnounced);

                $this->seriesRepository->save($series);

                $this->eventBus->dispatch(new SeriesUpdatedEvent($series));

                ++$updatedCount;
                if ($series->getAnnounce()) {
                    ++$announcedCount;
                } else {
                    ++$unAnnouncedCount;
                }

                $this->logger->info('Series announce toggled successfully', [
                    'id' => $seriesId,
                    'title' => $series->getTitle(),
                    'announce' => $series->getAnnounce(),
                    'was_announced' => $wasAnnounced,
                ]);
            } catch (SeriesNotFoundException $e) {
                $failedIds[] = $seriesId;
                $errors[$seriesId] = 'Series not found';
                $this->logger->warning('Series not found for toggle announce', ['id' => $seriesId]);
            } catch (\Exception $e) {
                $failedIds[] = $seriesId;
                $errors[$seriesId] = $e->getMessage();
                $this->logger->error('Error toggling announce for series', [
                    'id' => $seriesId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return new BulkToggleAnnounceSeriesResponse(
            updatedCount: $updatedCount,
            announcedCount: $announcedCount,
            unAnnouncedCount: $unAnnouncedCount,
            failedIds: $failedIds,
            errors: $errors
        );
    }
}
