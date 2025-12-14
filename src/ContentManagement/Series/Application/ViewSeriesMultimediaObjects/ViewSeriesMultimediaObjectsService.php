<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\ViewSeriesMultimediaObjects;

use App\ContentManagement\Series\Domain\Repository\SeriesRepositoryInterface;

final class ViewSeriesMultimediaObjectsService
{
    public function __construct(private readonly SeriesRepositoryInterface $repository) {}

    public function __invoke(ViewSeriesMultimediaObjectsRequest $request): ViewSeriesMultimediaObjectsResponse
    {
        ViewSeriesMultimediaObjectsValidator::validate($request);

        $offset = ($request->page - 1) * $request->limit;

        $seriesId = $request->filters['series_id'] ?? $request->filters['series.id'] ?? null;

        if (!$seriesId) {
            throw new \InvalidArgumentException('series_id is required in filters');
        }

        $multimediaObjects = $this->repository->findMultimediaObjectsBySeries(
            seriesId: $seriesId,
            offset: $offset,
            limit: $request->limit,
            sort: $request->sort ?? 'title',
            order: $request->order ?? 'asc'
        );

        $total = $this->repository->countMultimediaObjects($seriesId);

        return new ViewSeriesMultimediaObjectsResponse($multimediaObjects, $total);
    }
}
