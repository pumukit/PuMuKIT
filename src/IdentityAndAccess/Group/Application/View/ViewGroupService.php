<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\View;

use App\IdentityAndAccess\Group\Application\Find\FindGroupRequest;
use App\IdentityAndAccess\Group\Application\Find\FindGroupService;

final class ViewGroupService
{
    public function __construct(
        private FindGroupService $findGroupService
    ) {}

    public function __invoke(ViewGroupRequest $request): ViewGroupResponse
    {
        ViewGroupValidator::validate($request);

        $findRequest = new FindGroupRequest($request->id);
        $findResponse = ($this->findGroupService)($findRequest);

        return new ViewGroupResponse(
            $findResponse->group,
            $request->tab
        );
    }
}

