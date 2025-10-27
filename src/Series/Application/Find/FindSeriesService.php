<?php

namespace App\Series\Application\Find;

use App\Series\Application\Find\FindSeriesRequest;
use App\Series\Application\Find\FindSeriesResponse;
use App\Series\Domain\SeriesRepositoryInterface;
use App\Series\Domain\Exception\SeriesNotFoundException;

final class FindSeriesService
{
    public function __construct(private SeriesRepositoryInterface $repository) {}

    public function __invoke(FindSeriesRequest $request): FindSeriesResponse
    {
        FindSeriesValidator::validate($request);

        $series = $this->repository->find($request->id);

        if (!$series) {
            throw new SeriesNotFoundException($request->id);
        }

        return new FindSeriesResponse($series);
    }
}
