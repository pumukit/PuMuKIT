<?php

namespace App\IdentityAndAccess\Application\View;

use App\IdentityAndAccess\Application\Find\FindUserRequest;
use App\IdentityAndAccess\Application\Find\FindUserService;

final class ViewUserService
{
    public function __construct(private FindUserService $findUserService) {}

    public function __invoke(ViewUserRequest $request): ViewUserResponse
    {
        ViewUserValidator::validate($request);

        $findRequest = new FindUserRequest($request->id);
        $findResponse = ($this->findUserService)($findRequest);

        return new ViewUserResponse($findResponse->user);
    }
}
