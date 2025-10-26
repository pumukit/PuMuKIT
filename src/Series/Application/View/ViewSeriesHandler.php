<?php

namespace App\Series\Application\View;

use App\Series\Domain\SeriesRepositoryInterface;
use Pumukit\SchemaBundle\Document\Series;
use App\Series\Application\View\ViewSeriesResponse;

final class ViewSeriesHandler
{
    public function __construct(private SeriesRepositoryInterface $repository) {}

    public function execute(ViewSeriesRequest $request): ViewSeriesResponse
    {
        ViewSeriesValidator::validate($request);

        $series = $this->repository->find($request->id);

        if (!$series) {
            throw new \RuntimeException(sprintf('Series with ID "%s" not found', $request->id));
        }

        return new ViewSeriesResponse($series, [], []);
    }
}
