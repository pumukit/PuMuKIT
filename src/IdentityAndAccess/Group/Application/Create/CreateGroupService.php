<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\Create;

use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use Pumukit\SchemaBundle\Document\Group;

final class CreateGroupService
{
    public function __construct(
        private GroupRepositoryInterface $repository
    ) {}

    public function __invoke(CreateGroupRequest $request): CreateGroupResponse
    {
        CreateGroupValidator::validate($request);

        $group = new Group($request->key);
        $group->setName($request->name);
        $group->setOrigin($request->origin);

        if ($request->comments) {
            $group->setComments($request->comments);
        }

        $this->repository->save($group);

        return new CreateGroupResponse($group);
    }
}
