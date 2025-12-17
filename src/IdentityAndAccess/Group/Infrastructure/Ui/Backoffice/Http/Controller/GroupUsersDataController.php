<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use App\IdentityAndAccess\Group\Domain\Repository\GroupUserRepositoryInterface;
use App\IdentityAndAccess\Group\Domain\ValueObject\GroupId;
use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Presenter\GroupUserDataTablePresenter;
use Pumukit\SchemaBundle\Document\Group;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class GroupUsersDataController extends AbstractController
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly GroupUserDataTablePresenter $presenter,
        private readonly GroupUserRepositoryInterface $groupUserRepository,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $group = $this->groupRepository->find($id);

        if (!$group instanceof Group) {
            return $this->json([
                'total' => 0,
                'rows' => [],
            ]);
        }

        $offset = (int) $request->query->get('offset', '0');
        $limit = (int) $request->query->get('limit', '1');
        $sort = $request->query->get('sort', 'title');
        $order = $request->query->get('order', 'asc');
        $page = (int) floor($offset / $limit) + 1;

        $users = $this->groupUserRepository->findPaginatedByGroupId(
            GroupId::fromString($group->getId()),
            $page,
            $limit,
            $sort,
            $order
        );

        $total = $this->groupUserRepository->countUsersByGroupId(GroupId::fromString($group->getId()));

        $rows = [];
        foreach ($users as $user) {
            $rows[] = $this->presenter->present($user, $id);
        }

        return $this->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }
}
