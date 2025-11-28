<?php

declare(strict_types=1);

namespace App\Series\Application\ViewSeriesOwners;

use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\Domain\Repository\SeriesRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class ViewSeriesOwnersService
{
    public function __construct(
        private readonly SeriesRepositoryInterface $seriesRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function __invoke(ViewSeriesOwnersRequest $request): ViewSeriesOwnersResponse
    {
        $series = $this->seriesRepository->find($request->seriesId);

        if (!$series) {
            throw new SeriesNotFoundException($request->seriesId);
        }

        $ownerIds = $series->getOwnerIds();
        if (empty($ownerIds)) {
            return new ViewSeriesOwnersResponse([]);
        }

        $owners = $this->userRepository->findByIds($ownerIds);

        return new ViewSeriesOwnersResponse($owners);
    }
}

