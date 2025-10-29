<?php

namespace App\Series\Application\Find;

use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\Domain\Repository\SeriesRepositoryInterface;

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
