<?php

namespace App\Series\Application\ListSeries;

use App\Series\Domain\SeriesRepositoryInterface;

class ListSeriesHandler
{
    public function __construct(private SeriesRepositoryInterface $repository) {}

    public function execute(ListSeriesRequest $request): ListSeriesResponse
    {
        ListSeriesValidator::validate($request);

        $series = $this->repository->findByFilters($request->filters);

        $seriesWithCounts = [];
        foreach ($series as $oneSeries) {
            $seriesWithCounts[] = [
                'oneSeries' => $oneSeries,
                'objectCount' => $this->repository->countMultimediaObjects($oneSeries->getId()),
                'eventCount' => $this->repository->countEventMultimediaObjects($oneSeries->getId()),
            ];
        }

        return new ListSeriesResponse($seriesWithCounts);
    }
}
