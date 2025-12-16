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

        if ($request->name !== null) {
            $permissionProfile->setName($request->name);
        }

        if ($request->permissions !== null) {
            $permissionProfile->setPermissions($request->permissions);
        }

        if ($request->system !== null) {
            $permissionProfile->setSystem($request->system);
        }

        if ($request->default !== null) {
            $permissionProfile->setDefault($request->default);
        }

        if ($request->scope !== null) {
            $permissionProfile->setScope($request->scope);
        }

        $this->repository->save($permissionProfile);

        return new UpdatePermissionProfileResponse($permissionProfile);
    }
}

