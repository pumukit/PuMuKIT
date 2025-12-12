<?php

namespace App\IdentityAndAccess\User\Application\Delete;

use Pumukit\SchemaBundle\Document\User;
use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;

final class DeleteUserService
{
    public function __construct(
        private readonly UserRepositoryInterface $repository
    ) {}

    public function __invoke(DeleteUserRequest $request): DeleteUserResponse
    {
        DeleteUserValidator::validate($request);

        $user = $this->repository->find($request->id);

        if (!$user instanceof User) {
            return new DeleteUserResponse(
                success: false,
                message: sprintf('User with id "%s" not found.', $request->id)
            );
        }

        $username = $user->getUsername();

        $this->repository->delete($user);

        return new DeleteUserResponse(
            success: true,
            message: sprintf('User "%s" deleted successfully.', $username)
        );
    }
}
