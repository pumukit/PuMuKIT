<?php

declare(strict_types=1);

namespace App\Series\Application\Find;

use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\Domain\Repository\SeriesRepositoryInterface;

final class FindSeriesService
{
    public function __construct(private readonly SeriesRepositoryInterface $repository) {}

    public function __invoke(FindSeriesRequest $request): FindSeriesResponse
    {
        $series = $this->repository->find($request->id);

        if (!$series) {
            throw new SeriesNotFoundException($request->id);
        }

        return new FindSeriesResponse($series);
    }
}
