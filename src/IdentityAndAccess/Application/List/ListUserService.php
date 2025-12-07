<?php

namespace App\IdentityAndAccess\Application\List;

use App\IdentityAndAccess\Domain\Repository\UserRepositoryInterface;

final class ListUserService
{
    public function __construct(private UserRepositoryInterface $repository) {}

    public function __invoke(ListUserRequest $request): ListUserResponse
    {
        ListUserValidator::validate($request);

        $users = $this->repository->findByFiltersPaginated(
            $request->filters,
            $request->page,
            $request->limit,
            $request->sort,
            $request->order
        );

        $total = $this->repository->countByFilters($request->filters);

        return new ListUserResponse($users, $total);
    }
}
