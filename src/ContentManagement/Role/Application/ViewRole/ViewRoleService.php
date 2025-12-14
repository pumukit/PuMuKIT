<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\ViewRole;

use Pumukit\SchemaBundle\Document\Role;
use App\ContentManagement\Role\Domain\Exception\RoleNotFoundException;
use App\ContentManagement\Role\Domain\Repository\RoleRepositoryInterface;

final class ViewRoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository
    ) {}

    public function __invoke(ViewRoleRequest $request): ViewRoleResponse
    {
        ViewRoleValidator::validate($request);

        $role = $this->roleRepository->find($request->id);
        if (!$role instanceof Role) {
            throw RoleNotFoundException::withId($request->id);
        }

        return new ViewRoleResponse($role);
    }
}
