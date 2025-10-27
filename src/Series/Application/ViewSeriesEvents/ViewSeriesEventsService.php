<?php

namespace App\Series\Application\ViewSeriesEvents;

use App\Series\Domain\SeriesRepositoryInterface;

final class ViewSeriesEventsService
{
    public function __construct(private SeriesRepositoryInterface $repository) {}

    public function __invoke(ViewSeriesEventsRequest $request): ViewSeriesEventsResponse
    {
        ViewSeriesEventsValidator::validate($request);

        $offset = ($request->page - 1) * $request->limit;

        $multimediaObjects = $this->repository->findEventsBySeries(
            seriesId: $request->filters['series.id'],
            offset: $offset,
            limit: $request->limit,
            sort: $request->sort ?? 'title',
            order: $request->order ?? 'asc'
        );

        $total = $this->repository->countEvents($request->filters['series.id']);

        return new ViewSeriesEventsResponse($multimediaObjects, $total);
    }
}
