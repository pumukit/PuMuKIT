<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\DeleteRole;

use App\ContentManagement\Role\Domain\Event\RoleDeletedEvent;
use App\ContentManagement\Role\Domain\Exception\RoleNotFoundException;
use App\ContentManagement\Role\Domain\Repository\RoleRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final readonly class DeleteRoleService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(DeleteRoleRequest $request): void
    {
        $role = $this->roleRepository->find($request->id);
        if (!$role) {
            throw RoleNotFoundException::withId($request->id);
        }
        $this->roleRepository->delete($role);
    }
}

