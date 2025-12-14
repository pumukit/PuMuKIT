<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\CreateRole;

use App\ContentManagement\Role\Domain\Exception\RoleAlreadyExistsException;
use App\ContentManagement\Role\Domain\Repository\RoleRepositoryInterface;
use Pumukit\SchemaBundle\Document\Role;

final class CreateRoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function __invoke(CreateRoleRequest $request): CreateRoleResponse
    {
        CreateRoleValidator::validate($request);

        $existingRole = $this->roleRepository->findByCod($request->cod);
        if ($existingRole !== null) {
            throw RoleAlreadyExistsException::withCod($request->cod);
        }

        $role = new Role();
        $role->setCod($request->cod);
        $role->setI18nName($request->name);
        $role->setI18nText($request->text);

        if ($request->xml) {
            $role->setXml($request->xml);
        }

        $role->setDisplay($request->display);
        $role->setReadOnly($request->readOnly);

        $this->roleRepository->save($role);

        return new CreateRoleResponse($role);
    }
}
