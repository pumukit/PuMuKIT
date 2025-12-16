<?php

namespace App\IdentityAndAccess\Authorization\Application\List;

use App\IdentityAndAccess\Authorization\Domain\Repository\PermissionProfileRepositoryInterface;

final class ListPermissionProfileService
{
    public function __construct(private PermissionProfileRepositoryInterface $repository) {}

    public function __invoke(ListPermissionProfileRequest $request): ListPermissionProfileResponse
    {
        ListPermissionProfileValidator::validate($request);

        $permissionProfiles = $this->repository->findByFiltersPaginated(
            $request->filters,
            $request->page,
            $request->limit,
            $request->sort,
            $request->order
        );

        $total = $this->repository->countByFilters($request->filters);

        return new ListPermissionProfileResponse($permissionProfiles, $total);
    }
}
