<?php

namespace App\Series\Application\ViewSeriesOwners;

use App\User\Domain\UserRepositoryInterface;
use Pumukit\SchemaBundle\Document\Series;

class GetSeriesOwnersHandler
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    public function handle(Series $series): array
    {
        $ownerIds = $series->getOwnerIds();
        if (empty($ownerIds)) {
            return [];
        }

        return $this->userRepository->findByIds($ownerIds);
    }
}
