<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Create;

use App\IdentityAndAccess\Authorization\Domain\Repository\PermissionProfileRepositoryInterface;
use Pumukit\SchemaBundle\Document\PermissionProfile;

final class CreatePermissionProfileService
{
    public function __construct(
        private PermissionProfileRepositoryInterface $repository
    ) {}

    public function __invoke(CreatePermissionProfileRequest $request): CreatePermissionProfileResponse
    {
        CreatePermissionProfileValidator::validate($request);

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName($request->name);
        $permissionProfile->setPermissions($request->permissions);
        $permissionProfile->setSystem($request->system);
        $permissionProfile->setDefault($request->default);
        $permissionProfile->setScope($request->scope);

        $this->repository->save($permissionProfile);

        return new CreatePermissionProfileResponse($permissionProfile);
    }
}
