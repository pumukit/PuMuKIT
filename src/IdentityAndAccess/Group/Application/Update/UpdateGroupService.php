<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\Update;

use App\IdentityAndAccess\Group\Domain\Exception\GroupNotFoundException;
use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use Pumukit\SchemaBundle\Document\Group;

final class UpdateGroupService
{
    public function __construct(
        private GroupRepositoryInterface $repository
    ) {}

    public function __invoke(UpdateGroupRequest $request): UpdateGroupResponse
    {
        UpdateGroupValidator::validate($request);

        $group = $this->repository->find($request->id);

        if (!$group instanceof Group) {
            throw new GroupNotFoundException($request->id);
        }

        if (null !== $request->key) {
            $group->setKey($request->key);
        }

        if (null !== $request->name) {
            $group->setName($request->name);
        }

        if (null !== $request->comments) {
            $group->setComments($request->comments);
        }

        if (null !== $request->origin) {
            $group->setOrigin($request->origin);
        }

        $group->setUpdatedAt(new \DateTime());

        $this->repository->save($group);

        return new UpdateGroupResponse($group);
    }
}
