<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Update;

use App\IdentityAndAccess\Authorization\Domain\Exception\PermissionProfileNotFoundException;
use App\IdentityAndAccess\Authorization\Domain\Repository\PermissionProfileRepositoryInterface;
use Pumukit\SchemaBundle\Document\PermissionProfile;

final class UpdatePermissionProfileService
{
    public function __construct(
        private PermissionProfileRepositoryInterface $repository
    ) {}

    public function __invoke(UpdatePermissionProfileRequest $request): UpdatePermissionProfileResponse
    {
        UpdatePermissionProfileValidator::validate($request);

        $permissionProfile = $this->repository->find($request->id);

        if (!$permissionProfile instanceof PermissionProfile) {
            throw new PermissionProfileNotFoundException($request->id);
        }

        if (null !== $request->name) {
            $permissionProfile->setName($request->name);
        }

        if (null !== $request->permissions) {
            $permissionProfile->setPermissions($request->permissions);
        }

        if (null !== $request->system) {
            $permissionProfile->setSystem($request->system);
        }

        if (null !== $request->default) {
            $permissionProfile->setDefault($request->default);
        }

        if (null !== $request->scope) {
            $permissionProfile->setScope($request->scope);
        }

        $this->repository->save($permissionProfile);

        return new UpdatePermissionProfileResponse($permissionProfile);
    }
}
