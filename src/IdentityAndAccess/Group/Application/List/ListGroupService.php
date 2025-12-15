<?php

namespace App\IdentityAndAccess\Group\Application\List;

use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;

final class ListGroupService
{
    public function __construct(private GroupRepositoryInterface $repository) {}

    public function __invoke(ListGroupRequest $request): ListGroupResponse
    {
        ListGroupValidator::validate($request);

        $groups = $this->repository->findByFiltersPaginated(
            $request->filters,
            $request->page,
            $request->limit,
            $request->sort,
            $request->order
        );

        $total = $this->repository->countByFilters($request->filters);

        return new ListGroupResponse($groups, $total);
    }
}
