<?php

namespace App\Series\Application\ViewSeries;

use App\Series\Domain\SeriesRepositoryInterface;
use App\MultimediaObject\Domain\MultimediaObjectRepositoryInterface;

final class ViewSeriesHandler
{
    public function __construct(
        private SeriesRepositoryInterface $seriesRepository,
        private MultimediaObjectRepositoryInterface $multimediaObjectRepository
    ) {}

    public function handle(ViewSeriesQuery $query): ViewSeriesResponse
    {
        $series = $this->seriesRepository->find($query->id());

        if (!$series) {
            throw new \RuntimeException('Series not found');
        }

        $oms = $this->multimediaObjectRepository->findBySeriesId($query->id());

        return new ViewSeriesResponse($series, $oms);
    }
}
