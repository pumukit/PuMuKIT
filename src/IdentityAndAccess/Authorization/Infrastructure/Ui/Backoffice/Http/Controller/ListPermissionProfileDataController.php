<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Authorization\Application\List\ListPermissionProfileRequest;
use App\IdentityAndAccess\Authorization\Application\List\ListPermissionProfileService;
use App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\Presenter\PermissionProfileDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class ListPermissionProfileDataController extends AbstractController
{
    public function __construct(
        private readonly ListPermissionProfileService $listPermissionProfileService,
        private readonly PermissionProfileDataTablePresenter $presenter
    ) {}

    public function __invoke(
        Request $request,
        RouterInterface $router
    ): JsonResponse {
        $page = (int) $request->query->get('page', '1');
        $limit = (int) $request->query->get('limit', '10');
        $sort = $request->query->get('sort', 'name');
        $order = $request->query->get('order', 'asc');

        $filters = [];

        if (str_starts_with($sort, 'permission_profile.')) {
            $sort = substr($sort, 19);
        }

        $dto = new ListPermissionProfileRequest(
            page: $page,
            limit: $limit,
            filters: $filters,
            sort: $sort,
            order: $order
        );

        $permissionProfileResponse = ($this->listPermissionProfileService)($dto);

        $rows = [];
        foreach ($permissionProfileResponse->permissionProfiles as $permissionProfile) {
            $rows[] = $this->presenter->present($permissionProfile);
        }

        return $this->json([
            'total' => $permissionProfileResponse->total,
            'rows' => $rows,
        ]);
    }
}

