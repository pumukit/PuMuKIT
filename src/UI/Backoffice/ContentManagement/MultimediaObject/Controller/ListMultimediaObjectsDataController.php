<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\MultimediaObject\Controller;

use App\ContentManagement\MultimediaObject\Application\List\ListMultimediaObjectsRequest;
use App\ContentManagement\MultimediaObject\Application\List\ListMultimediaObjectsService;
use App\UI\Backoffice\ContentManagement\MultimediaObject\Presenter\MultimediaObjectDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ListMultimediaObjectsDataController extends AbstractController
{
    public function __construct(
        private readonly ListMultimediaObjectsService $listMultimediaObjectsService,
        private readonly MultimediaObjectDataTablePresenter $presenter
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 10);
        $sort = $request->query->get('sort', 'public_date');
        $order = $request->query->get('order', 'desc');
        $search = $request->query->get('search', '');

        $page = (int) floor($offset / $limit) + 1;

        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $dto = new ListMultimediaObjectsRequest(
            page: $page,
            limit: $limit,
            sort: $sort,
            order: $order,
            filters: $filters
        );

        $multimediaObjectsResponse = ($this->listMultimediaObjectsService)($dto);

        $rows = [];
        foreach ($multimediaObjectsResponse->multimediaObjects as $multimediaObject) {
            $rows[] = $this->presenter->present($multimediaObject, $request->getLocale());
        }

        return $this->json([
            'total' => $multimediaObjectsResponse->total,
            'rows' => $rows,
        ]);
    }
}
