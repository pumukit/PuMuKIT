<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\DeleteRole;

use Pumukit\SchemaBundle\Document\Role;
use App\ContentManagement\Role\Domain\Exception\RoleNotFoundException;
use App\ContentManagement\Role\Domain\Repository\RoleRepositoryInterface;

final class DeleteRoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function __invoke(DeleteRoleRequest $request): DeleteRoleResponse
    {
        DeleteRoleValidator::validate($request);

        $role = $this->roleRepository->find($request->id);
        if (!$role instanceof Role) {
            throw RoleNotFoundException::withId($request->id);
        }

        $this->roleRepository->delete($role);

        return new DeleteRoleResponse($role->getId());
    }
}
