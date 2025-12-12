<?php

namespace App\ContentManagement\Series\Infrastructure\Factory;

use App\ContentManagement\Series\Domain\Factory\SeriesFactoryInterface;
use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use App\IdentityAndAccess\User\Domain\ValueObject\UserId;
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

    public function update(Series $series): Series
    {
        return $series;
    }
}
