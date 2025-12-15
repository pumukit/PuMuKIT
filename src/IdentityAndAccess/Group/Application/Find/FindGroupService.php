<?php

namespace App\IdentityAndAccess\Group\Application\Find;

use App\IdentityAndAccess\Group\Domain\Exception\GroupNotFoundException;
use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use Pumukit\SchemaBundle\Document\Group;

final class FindGroupService
{
    public function __construct(private GroupRepositoryInterface $repository) {}

    public function __invoke(FindGroupRequest $request): FindGroupResponse
    {
        FindGroupValidator::validate($request);

        $group = $this->repository->find($request->id);

        if (!$group instanceof Group) {
            throw new GroupNotFoundException($request->id);
        }

        return new FindGroupResponse($group);
    }
}
