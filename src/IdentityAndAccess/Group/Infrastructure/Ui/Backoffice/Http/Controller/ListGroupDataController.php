<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Group\Application\List\ListGroupRequest;
use App\IdentityAndAccess\Group\Application\List\ListGroupService;
use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Presenter\GroupDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class ListGroupDataController extends AbstractController
{
    public function __construct(
        private readonly ListGroupService $listGroupService,
        private readonly GroupDataTablePresenter $presenter
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $offset = (int) $request->query->get('offset', '0');
        $limit = (int) $request->query->get('limit', '10');
        $sort = $request->query->get('sort', 'key');
        $order = $request->query->get('order', 'asc');

        $filters = [];

        if (str_starts_with($sort, 'group.')) {
            $sort = substr($sort, 6);
        }

        $page = (int) floor($offset / $limit) + 1;

        $dto = new ListGroupRequest(
            filters: $filters,
            page: $page,
            limit: $limit,
            sort: $sort,
            order: $order
        );

        $groupResponse = ($this->listGroupService)($dto);

        $rows = [];
        foreach ($groupResponse->groups as $group) {
            $rows[] = $this->presenter->present($group);
        }

        return $this->json([
            'total' => $groupResponse->total,
            'rows' => $rows,
        ]);
    }
}
