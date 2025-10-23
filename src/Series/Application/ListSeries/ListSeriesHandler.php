<?php

namespace App\Series\Application\ListSeries;

use App\Series\Domain\SeriesRepositoryInterface;

final class ListSeriesHandler
{
    public function __construct(private SeriesRepositoryInterface $repository) {}

    public function handle(ListSeriesQuery $query): ListSeriesResponse
    {
        $series = $this->repository->findAll();
        return new ListSeriesResponse($series);
    }
}
