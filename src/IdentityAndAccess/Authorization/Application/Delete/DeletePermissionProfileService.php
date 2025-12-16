<?php

namespace App\IdentityAndAccess\Authorization\Application\Delete;

use App\IdentityAndAccess\Authorization\Domain\Repository\PermissionProfileRepositoryInterface;
use Pumukit\SchemaBundle\Document\PermissionProfile;

final class DeletePermissionProfileService
{
    public function __construct(
        private readonly PermissionProfileRepositoryInterface $repository
    ) {}

    public function __invoke(DeletePermissionProfileRequest $request): DeletePermissionProfileResponse
    {
        DeletePermissionProfileValidator::validate($request);

        $permissionProfile = $this->repository->find($request->id);

        if (!$permissionProfile instanceof PermissionProfile) {
            return new DeletePermissionProfileResponse(
                success: false,
                message: sprintf('PermissionProfile with id "%s" not found.', $request->id)
            );
        }

        if ($permissionProfile->isSystem()) {
            return new DeletePermissionProfileResponse(
                success: false,
                message: sprintf('Cannot delete system PermissionProfile "%s".', $permissionProfile->getName())
            );
        }

        $name = $permissionProfile->getName();

        $this->repository->delete($permissionProfile);

        return new DeletePermissionProfileResponse(
            success: true,
            message: sprintf('PermissionProfile "%s" deleted successfully.', $name)
        );
    }
}
