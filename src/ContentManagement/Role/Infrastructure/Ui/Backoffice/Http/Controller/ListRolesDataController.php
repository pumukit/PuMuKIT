<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Infrastructure\Ui\Backoffice\Http\Controller;

use App\ContentManagement\Role\Application\ListRoles\ListRolesRequest;
use App\ContentManagement\Role\Application\ListRoles\ListRolesService;
use App\ContentManagement\Role\Infrastructure\Ui\Backoffice\Http\Presenter\RoleDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ListRolesDataController extends AbstractController
{
    public function __construct(
        private ListRolesService $listRolesService,
        private RoleDataTablePresenter $presenter
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', '1');
        $limit = (int) $request->query->get('limit', '10');
        $sort = $request->query->get('sort', 'public_date');
        $order = $request->query->get('order', 'desc');
        $search = $request->query->get('search', '');

        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $dto = new ListRolesRequest(
            page: $page,
            limit: $limit,
            sort: $sort,
            order: $order,
            filters: $filters
        );

        $response = ($this->listRolesService)($dto);

        $rows = [];
        foreach ($response->roles as $role) {
            $rows[] = $this->presenter->present($role);
        }

        return $this->json([
            'total' => $response->total,
            'rows' => $rows,
        ]);
    }
}
