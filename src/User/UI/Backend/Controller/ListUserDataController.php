<?php

namespace App\User\UI\Backend\Controller;

use App\User\Application\List\ListUserRequest;
use App\User\Application\List\ListUserService;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class ListUserDataController extends AbstractController
{
    public function __construct(
        private ListUserService $listUserService,
        private UserRepositoryInterface $userRepository
    ) {}

    public function __invoke(
        Request $request,
        RouterInterface $router
    ): JsonResponse {
        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 10);
        $sort = $request->query->get('sort', 'fullName');
        $order = $request->query->get('order', 'asc');

        $page = (int) floor($offset / $limit) + 1;

        $filters = [];

        if(str_starts_with($sort, 'user.')) {
            $sort = substr($sort, 5);
        }

        $dto = new ListUserRequest(
            page: $page,
            limit: $limit,
            filters: $filters,
            sort: $sort,
            order: $order
        );

        $userResponse = ($this->listUserService)($dto);

        $rows = [];
        foreach ($userResponse->users as $item) {
            $actionsHtml = sprintf(
                '<div class="d-flex gap-1 justify-content-end">
        <a href="%s" class="btn btn-sm"><i class="fa fa-eye"></i></a>
        <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this user?\');">
            <button type="submit" class="btn btn-sm">
                <i class="fa fa-trash"></i>
            </button>
        </form>
    </div>',
                "#",
                "#"
            );

            $rows[] = [
                'user' => $item,
                'actions' => $actionsHtml,
            ];
        }

        return $this->json([
            'total' => $userResponse->total,
            'rows' => $rows,
        ]);
    }
}
