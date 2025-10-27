<?php

namespace App\Series\Application\Clone;

use App\Series\Domain\SeriesRepositoryInterface;
use App\Series\Domain\Exception\SeriesNotFoundException;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\FactoryService;

final class CloneSeriesService
{
    public function __construct(
        private SeriesRepositoryInterface $repository,
        private FactoryService $factoryService
    ) {}

    public function __invoke(string $seriesId): Series
    {
        $originalSeries = $this->repository->find($seriesId);

        if (!$originalSeries) {
            throw new SeriesNotFoundException($seriesId);
        }

        try {
            return $this->factoryService->cloneSeries($originalSeries);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}

