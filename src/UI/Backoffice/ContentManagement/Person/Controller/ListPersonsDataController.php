<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Person\Controller;

use App\ContentManagement\Person\Application\ListPersons\ListPersonsRequest;
use App\ContentManagement\Person\Application\ListPersons\ListPersonsService;
use App\UI\Backoffice\ContentManagement\Person\Presenter\PersonDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ListPersonsDataController extends AbstractController
{
    public function __construct(
        private ListPersonsService $listPersonsService,
        private PersonDataTablePresenter $presenter
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

        $listPersonsRequest = new ListPersonsRequest(
            page: $page,
            limit: $limit,
            sort: $sort,
            order: $order,
            filters: $filters
        );
        $response = ($this->listPersonsService)($listPersonsRequest);

        $rows = [];
        foreach ($response->persons as $person) {
            $rows[] = $this->presenter->present($person);
        }

        return new JsonResponse([
            'total' => count($rows),
            'rows' => $rows,
        ]);
    }
}
