<?php

namespace App\UI\Backoffice\IdentityAndAccess\Controller;

use App\IdentityAndAccess\Application\List\ListUserRequest;
use App\IdentityAndAccess\Application\List\ListUserService;
use App\UI\Backoffice\IdentityAndAccess\Presenter\UserDataTablePresenter;
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
        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 10);
        $sort = $request->query->get('sort', 'fullName');
        $order = $request->query->get('order', 'asc');

        $page = (int) floor($offset / $limit) + 1;

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
            $rows[] = $this->presenter->present(
                $user,
                $request->getScheme(),
                $request->getHost(),
                $request->getLocale()
            );
        }

        return $this->json([
            'total' => $userResponse->total,
            'rows' => $rows,
        ]);
    }
}
