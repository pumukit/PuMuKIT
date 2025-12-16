<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\View;

use App\IdentityAndAccess\Authorization\Application\Find\FindPermissionProfileRequest;
use App\IdentityAndAccess\Authorization\Application\Find\FindPermissionProfileService;

final class ViewPermissionProfileService
{
    public function __construct(
        private FindPermissionProfileService $findPermissionProfileService
    ) {}

    public function __invoke(ViewPermissionProfileRequest $request): ViewPermissionProfileResponse
    {
        ViewPermissionProfileValidator::validate($request);

        $findRequest = new FindPermissionProfileRequest($request->id);
        $findResponse = ($this->findPermissionProfileService)($findRequest);

        return new ViewPermissionProfileResponse(
            $findResponse->permissionProfile,
            $request->tab
        );
    }
}

