<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\ViewRole;

use App\ContentManagement\Role\Domain\Exception\RoleNotFoundException;
use App\ContentManagement\Role\Domain\Repository\RoleRepositoryInterface;

final readonly class ViewRoleService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository
    ) {}

    public function __invoke(ViewRoleRequest $request): ViewRoleResponse
    {
        $role = $this->roleRepository->find($request->id);
        if (!$role) {
            throw RoleNotFoundException::withId($request->id);
        }

        return new ViewRoleResponse($role);
    }
}

