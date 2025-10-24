<?php

namespace App\Series\Infrastructure\Factory;

use App\Series\Domain\SeriesFactoryInterface;
use App\User\Domain\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\FactoryService;

final class SeriesFactoryAdapter implements SeriesFactoryInterface
{
    public function __construct(
        private FactoryService $factoryService,
        private UserRepositoryInterface $userRepository
    ) {}

    public function createForUser(UserId $userId, array $title = null): Series
    {
        $user = $this->userRepository->find($userId->toObjectId());

        return $this->factoryService->createSeries($user, $title);
    }
}
