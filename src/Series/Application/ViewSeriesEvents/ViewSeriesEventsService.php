<?php

declare(strict_types=1);

namespace App\Series\Application\ViewSeriesEvents;

use App\Series\Domain\Repository\SeriesRepositoryInterface;

final class ViewSeriesEventsService
{
    public function __construct(private readonly SeriesRepositoryInterface $repository) {}

    public function __invoke(ViewSeriesEventsRequest $request): ViewSeriesEventsResponse
    {
        $offset = ($request->page - 1) * $request->limit;

        // Support both 'series.id' and 'series_id' keys for backwards compatibility
        $seriesId = $request->filters['series_id'] ?? $request->filters['series.id'] ?? null;

        if (!$seriesId) {
            throw new \InvalidArgumentException('series_id is required in filters');
        }

        $multimediaObjects = $this->repository->findEventsBySeries(
            seriesId: $seriesId,
            offset: $offset,
            limit: $request->limit,
            sort: $request->sort ?? 'title',
            order: $request->order ?? 'asc'
        );

        $total = $this->repository->countEvents($seriesId);

        return new ViewSeriesEventsResponse($multimediaObjects, $total);
    }
}
