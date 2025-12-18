<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\User\Application\Search\SearchUsersByCriteriaRequest;
use App\IdentityAndAccess\User\Application\Search\SearchUsersByCriteriaService;
use App\IdentityAndAccess\User\Infrastructure\Ui\Backoffice\Http\Presenter\GroupUserDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class GroupUsersDataController extends AbstractController
{
    public function __construct(
        private readonly SearchUsersByCriteriaService $searchService,
        private readonly GroupUserDataTablePresenter $presenter
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $searchRequest = new SearchUsersByCriteriaRequest(
            filters: [['field' => 'groups', 'operator' => '=', 'value' => $id]],
            orderBy: $request->query->get('sort', 'name'),
            order: $request->query->get('order', 'asc'),
            limit: (int) $request->query->get('limit', '10'),
            offset: (int) $request->query->get('offset', '0')
        );

        $response = ($this->searchService)($searchRequest);

        return new JsonResponse([
            'total' => $response['total'],
            'rows' => array_map(fn ($user) => $this->presenter->present($user, $id), $response['items']),
        ]);
    }
}
