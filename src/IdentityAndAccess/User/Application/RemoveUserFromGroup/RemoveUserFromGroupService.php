<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\RemoveUserFromGroup;

use App\IdentityAndAccess\Group\Domain\Exception\GroupNotFoundException;
use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use App\IdentityAndAccess\User\Domain\Exception\UserNotFoundException;
use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;

final class RemoveUserFromGroupService
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function __invoke(RemoveUserFromGroupRequest $request): RemoveUserFromGroupResponse
    {
        RemoveUserFromGroupValidator::validate($request);

        $group = $this->groupRepository->find($request->groupId);
        if (!$group) {
            throw new GroupNotFoundException($request->groupId);
        }

        $user = $this->userRepository->find($request->userId);
        if (!$user) {
            throw new UserNotFoundException($request->userId);
        }

        $user->removeGroup($group);

        $this->userRepository->save($user);

        return new RemoveUserFromGroupResponse($user->getUsername());
    }
}
