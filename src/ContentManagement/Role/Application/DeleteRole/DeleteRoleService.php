<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\DeleteRole;

use App\ContentManagement\Role\Domain\Exception\RoleNotFoundException;
use App\ContentManagement\Role\Domain\Repository\RoleRepositoryInterface;

final readonly class DeleteRoleService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {}

    public function __invoke(DeleteRoleRequest $request): DeleteRoleResponse
    {
        $role = $this->roleRepository->find($request->id);
        if (!$role) {
            throw RoleNotFoundException::withId($request->id);
        }

        $this->roleRepository->delete($role);

        return new DeleteRoleResponse($role->getId());
    }
}
