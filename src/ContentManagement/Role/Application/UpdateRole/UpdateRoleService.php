<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\UpdateRole;

use App\ContentManagement\Role\Domain\Exception\RoleNotFoundException;
use App\ContentManagement\Role\Domain\Repository\RoleRepositoryInterface;

final readonly class UpdateRoleService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {}

    public function __invoke(UpdateRoleRequest $request): UpdateRoleResponse
    {
        $role = $this->roleRepository->find($request->id);
        if (!$role) {
            throw RoleNotFoundException::withId($request->id);
        }

        $role->setI18nName($request->name);
        $role->setI18nText($request->text);

        if (null !== $request->xml) {
            $role->setXml($request->xml);
        }

        $role->setDisplay($request->display);

        $this->roleRepository->save($role);

        return new UpdateRoleResponse($role);
    }
}
