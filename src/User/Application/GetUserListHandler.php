<?php

namespace App\User\Application;

use App\User\Domain\Repository\UserRepositoryInterface;

final class GetUserListHandler
{
    private UserRepositoryInterface $repository;

    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function handle(array $filters): array
    {
        return $this->repository->findByFilters($filters);
    }
}
