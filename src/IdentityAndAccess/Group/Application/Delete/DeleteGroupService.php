<?php

namespace App\IdentityAndAccess\Group\Application\Delete;

use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use Pumukit\SchemaBundle\Document\Group;

final class DeleteGroupService
{
    public function __construct(
        private readonly GroupRepositoryInterface $repository
    ) {}

    public function __invoke(DeleteGroupRequest $request): DeleteGroupResponse
    {
        DeleteGroupValidator::validate($request);

        $group = $this->repository->find($request->id);

        if (!$group instanceof Group) {
            return new DeleteGroupResponse(
                success: false,
                message: sprintf('Group with id "%s" not found.', $request->id)
            );
        }

        $key = $group->getKey();

        $this->repository->delete($group);

        return new DeleteGroupResponse(
            success: true,
            message: sprintf('Group "%s" deleted successfully.', $key)
        );
    }
}
