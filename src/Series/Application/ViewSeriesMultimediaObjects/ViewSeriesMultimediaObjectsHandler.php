<?php


namespace App\Series\Application\ViewSeriesMultimediaObjects;

use App\Series\Domain\SeriesRepositoryInterface;

final class ViewSeriesMultimediaObjectsHandler
{
    public function __construct(private SeriesRepositoryInterface $repository) {}

    public function handle(ViewSeriesMultimediaObjectsRequest $request): ViewSeriesMultimediaObjectsResponse
    {
        $offset = ($request->page - 1) * $request->limit;

        $filters = [];
        if ($request->search) {
            $filters['title'] = $request->search;
        }

        $multimediaObjects = $this->repository->findMultimediaObjectsBySeries(
            seriesId: $request->seriesId,
            offset: $offset,
            limit: $request->limit,
            sort: $request->sort ?? 'title',
            order: $request->order ?? 'asc'
        );

        $total = $this->repository->countMultimediaObjects($request->seriesId);

        return new ViewSeriesMultimediaObjectsResponse($multimediaObjects, $total);
    }
}
