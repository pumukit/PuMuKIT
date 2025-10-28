<?php

declare(strict_types=1);

namespace App\Series\Application\Clone;

use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\Domain\SeriesRepositoryInterface;
use Pumukit\SchemaBundle\Services\FactoryService;

final class CloneSeriesService
{
    public function __construct(
        private SeriesRepositoryInterface $repository,
        private FactoryService $factoryService
    ) {}

    public function __invoke(CloneSeriesRequest $request): CloneSeriesResponse
    {
        CloneSeriesValidator::validate($request);

        $originalSeries = $this->repository->find($request->seriesId);

        if (!$originalSeries) {
            throw new SeriesNotFoundException($request->seriesId);
        }

        $clonedSeries = $this->factoryService->cloneSeries($originalSeries);

        $multimediaObjects = $this->repository->findMultimediaObjectsBySeries($originalSeries->getId());

        $multimediaObjectsCount = count($multimediaObjects);

        $this->repository->save($clonedSeries);

        return new CloneSeriesResponse($clonedSeries, $multimediaObjectsCount);
    }
}
