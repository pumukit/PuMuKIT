<?php

namespace App\Playlist\Infrastructure\Factory;

use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use App\IdentityAndAccess\User\Domain\ValueObject\UserId;
use App\Playlist\Domain\Factory\PlaylistFactoryInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\FactoryService;

final class PlaylistFactoryAdapter implements PlaylistFactoryInterface
{
    public function __construct(
        private FactoryService $factoryService,
        private UserRepositoryInterface $userRepository
    ) {}

    public function createForUser(UserId $userId, array $title = null): Series
    {
        $user = $this->userRepository->find($userId->toObjectId());

        return $this->factoryService->createPlaylist($user, $title);
    }

    public function update(Series $playlist): Series
    {
        return $playlist;
    }
}
