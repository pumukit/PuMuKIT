<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\CreateRole;

use App\ContentManagement\Role\Domain\Exception\RoleAlreadyExistsException;
use App\ContentManagement\Role\Domain\Repository\RoleRepositoryInterface;
use App\Shared\Domain\EventBusInterface;
use Pumukit\SchemaBundle\Document\Role;

final readonly class CreateRoleService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(CreateRoleRequest $request): CreateRoleResponse
    {
        $existingRole = $this->roleRepository->findByCod($request->cod);
        if ($existingRole) {
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

