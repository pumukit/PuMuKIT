<?php

declare(strict_types=1);

namespace App\Series\Application\ViewSeriesOwners;

use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\Domain\Repository\SeriesRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class GetSeriesOwnersService
{
    public function __construct(
        private SeriesRepositoryInterface $seriesRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    public function __invoke(GetSeriesOwnersRequest $request): GetSeriesOwnersResponse
    {
        GetSeriesOwnersValidator::validate($request);

        $series = $this->seriesRepository->find($request->seriesId);

        if (!$series) {
            throw new SeriesNotFoundException($request->seriesId);
        }

        $ownerIds = $series->getOwnerIds();
        if (empty($ownerIds)) {
            return new GetSeriesOwnersResponse([]);
        }

        $owners = $this->userRepository->findByIds($ownerIds);

        return new GetSeriesOwnersResponse($owners);
    }
}
