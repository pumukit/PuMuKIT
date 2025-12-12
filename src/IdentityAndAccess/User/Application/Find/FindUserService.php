<?php

namespace App\IdentityAndAccess\User\Application\Find;

use Pumukit\SchemaBundle\Document\User;
use App\IdentityAndAccess\User\Domain\Exception\UserNotFoundException;
use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;

final class FindUserService
{
    public function __construct(private UserRepositoryInterface $repository) {}

    public function __invoke(FindUserRequest $request): FindUserResponse
    {
        FindUserValidator::validate($request);

        $user = $this->repository->find($request->id);

        if (!$user instanceof User) {
            throw new UserNotFoundException($request->id);
        }

        return new FindUserResponse($user);
    }
}
