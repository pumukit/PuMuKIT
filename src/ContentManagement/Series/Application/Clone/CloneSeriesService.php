<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Clone;

use App\ContentManagement\Series\Domain\Exception\SeriesNotFoundException;
use App\ContentManagement\Series\Domain\Repository\SeriesRepositoryInterface;
use Pumukit\SchemaBundle\Services\FactoryService;

final class CloneSeriesService
{
    public function __construct(
        private readonly SeriesRepositoryInterface $repository,
        private readonly FactoryService $factoryService
    ) {}

    public function __invoke(CloneSeriesRequest $request): CloneSeriesResponse
    {
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
