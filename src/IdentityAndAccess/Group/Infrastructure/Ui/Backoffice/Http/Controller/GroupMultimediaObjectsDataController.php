<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Group\Domain\Query\MultimediaObjectQueryInterface;
use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use App\IdentityAndAccess\Group\Domain\ValueObject\GroupId;
use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Presenter\GroupMultimediaObjectDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class GroupMultimediaObjectsDataController extends AbstractController
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly GroupMultimediaObjectDataTablePresenter $presenter,
        private readonly MultimediaObjectQueryInterface $multimediaObjectQuery
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $group = $this->groupRepository->find($id);
        if (!$group) {
            return $this->json(['total' => 0, 'rows' => []]);
        }

        $offset = (int) $request->query->get('offset', '0');
        $limit = (int) $request->query->get('limit', '10');
        $sort = $request->query->get('sort', 'title');
        $order = $request->query->get('order', 'asc');
        $page = (int) floor($offset / $limit) + 1;

        $total = $this->multimediaObjectQuery->countByGroupId(GroupId::fromString($group->getId()));

        $multimediaObjectsData = $this->multimediaObjectQuery->findPaginatedByGroupId(
            GroupId::fromString($group->getId()),
            $page,
            $limit,
            $sort,
            $order
        );

        $rows = [];
        foreach ($multimediaObjectsData as $data) {
            $rows[] = $this->presenter->present($data, $request->getLocale());
        }

        return $this->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }
}

