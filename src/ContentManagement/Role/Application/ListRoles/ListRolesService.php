<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\ListRoles;

use App\ContentManagement\Role\Domain\Repository\RoleRepositoryInterface;

final readonly class ListRolesService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository
    ) {}

    public function __invoke(ListRolesRequest $request): ListRolesResponse
    {
        $sort = [$request->sort => $request->order];

        $roles = $this->roleRepository->findAll(
            $request->page,
            $request->limit,
            $sort,
            $request->filters
        );

        $count = $this->roleRepository->countAll();

        return new ListRolesResponse(
            roles: $roles,
            total: $count,
            page: $request->page,
            limit: $request->limit
        );
    }
}
