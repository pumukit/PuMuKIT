<?php

namespace App\Series\Application\List;

use App\Series\Domain\SeriesRepositoryInterface;

class ListSeriesHandler
{
    public function __construct(private SeriesRepositoryInterface $repository) {}

    public function execute(ListSeriesRequest $request): ListSeriesResponse
    {
        ListSeriesValidator::validate($request);

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
