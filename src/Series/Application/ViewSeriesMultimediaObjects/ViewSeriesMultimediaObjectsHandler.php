<?php


namespace App\Series\Application\ViewSeriesMultimediaObjects;

use App\Series\Domain\SeriesRepositoryInterface;

final class ViewSeriesMultimediaObjectsHandler
{
    public function __construct(private SeriesRepositoryInterface $repository) {}

    public function handle(ViewSeriesMultimediaObjectsRequest $request): ViewSeriesMultimediaObjectsResponse
    {
        ViewSeriesMultimediaObjectsValidator::validate($request);

        $offset = ($request->page - 1) * $request->limit;

        $multimediaObjects = $this->repository->findMultimediaObjectsBySeries(
            seriesId: $request->filters['series.id'],
            offset: $offset,
            limit: $request->limit,
            sort: $request->sort ?? 'title',
            order: $request->order ?? 'asc'
        );

        $total = $this->repository->countMultimediaObjects($request->filters['series.id']);

        return new ViewSeriesMultimediaObjectsResponse($multimediaObjects, $total);
    }
}
