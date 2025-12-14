<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Find;

use App\ContentManagement\Series\Domain\Exception\SeriesNotFoundException;
use App\ContentManagement\Series\Domain\Repository\SeriesRepositoryInterface;

final class FindSeriesService
{
    public function __construct(private readonly SeriesRepositoryInterface $repository) {}

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
