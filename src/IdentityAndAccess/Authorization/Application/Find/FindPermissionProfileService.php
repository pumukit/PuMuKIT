<?php

namespace App\IdentityAndAccess\Authorization\Application\Find;

use App\IdentityAndAccess\Authorization\Domain\Exception\PermissionProfileNotFoundException;
use App\IdentityAndAccess\Authorization\Domain\Repository\PermissionProfileRepositoryInterface;
use Pumukit\SchemaBundle\Document\PermissionProfile;

final class FindPermissionProfileService
{
    public function __construct(private PermissionProfileRepositoryInterface $repository) {}

    public function __invoke(FindPermissionProfileRequest $request): FindPermissionProfileResponse
    {
        FindPermissionProfileValidator::validate($request);

        $permissionProfile = $this->repository->find($request->id);

        if (!$permissionProfile instanceof PermissionProfile) {
            throw new PermissionProfileNotFoundException($request->id);
        }

        return new FindPermissionProfileResponse($permissionProfile);
    }
}
