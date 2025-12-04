<?php

declare(strict_types=1);

namespace App\Series\Application\List;

use App\Series\Domain\Repository\SeriesRepositoryInterface;

final class ListSeriesService
{
    public function __construct(private readonly SeriesRepositoryInterface $repository) {}

    public function __invoke(ListSeriesRequest $request): ListSeriesResponse
    {
        $series = $this->repository->findByFiltersPaginated(
            $request->filters,
            $request->page,
            $request->limit,
            $request->sort,
            $request->order
        );

        $total = $this->repository->countByFilters($request->filters);

        return new ListSeriesResponse($series, $total);
    }
}
