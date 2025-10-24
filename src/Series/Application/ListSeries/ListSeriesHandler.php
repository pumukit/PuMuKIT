<?php

namespace App\Series\Application\ListSeries;

use App\Series\Domain\SeriesRepositoryInterface;

final class ListSeriesHandler
{
    public function __construct(private SeriesRepositoryInterface $repository) {}

    public function handle(array $filters): ListSeriesResponse
    {
        $series = $this->repository->findByFilters($filters);

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
