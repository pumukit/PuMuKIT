<?php

namespace App\UI\Backoffice\IdentityAndAccess\User\Controller;

use App\IdentityAndAccess\User\Application\List\ListUserRequest;
use App\IdentityAndAccess\User\Application\List\ListUserService;
use App\UI\Backoffice\IdentityAndAccess\User\Presenter\UserDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class ListUserDataController extends AbstractController
{
    public function __construct(
        private ListUserService $listUserService,
        private UserDataTablePresenter $presenter
    ) {}

    public function __invoke(
        Request $request,
        RouterInterface $router
    ): JsonResponse {
        $page = (int) $request->query->get('page', '1');
        $limit = (int) $request->query->get('limit', '10');
        $sort = $request->query->get('sort', 'fullName');
        $order = $request->query->get('order', 'asc');


        $filters = [];

        if (str_starts_with($sort, 'user.')) {
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
        foreach ($userResponse->users as $user) {
            $rows[] = $this->presenter->present($user);
        }

        return $this->json([
            'total' => $userResponse->total,
            'rows' => $rows,
        ]);
    }
}
