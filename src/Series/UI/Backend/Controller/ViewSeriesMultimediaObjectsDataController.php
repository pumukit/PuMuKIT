<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ViewSeriesMultimediaObjects\ViewSeriesMultimediaObjectsRequest;
use App\Series\Application\ViewSeriesMultimediaObjects\ViewSeriesMultimediaObjectsService;
use App\Series\UI\Backend\Presenter\MultimediaObjectDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ViewSeriesMultimediaObjectsDataController extends AbstractController
{
    public function __construct(
        private ViewSeriesMultimediaObjectsService $viewSeriesMultimediaObjectsService,
        private MultimediaObjectDataTablePresenter $presenter
    ) {}

    public function __invoke(
        Request $request,
        string $id,
    ): JsonResponse {
        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 10);
        $sort = $request->query->get('sort', 'title');
        $order = $request->query->get('order', 'asc');
        $search = $request->query->get('search', '');

        $page = (int) floor($offset / $limit) + 1;

        $filters = [
            'series_id' => $id,
        ];
        if ($search) {
            $filters['title'] = $search;
            $filters['subtitle'] = $search;
        }

        $dto = new ViewSeriesMultimediaObjectsRequest(
            page: $page,
            limit: $limit,
            filters: $filters,
            sort: $sort,
            order: $order,
        );

        $response = ($this->viewSeriesMultimediaObjectsService)($dto);

        $rows = [];
        foreach ($response->multimediaObjects as $multimediaObject) {
            $rows[] = $this->presenter->present(
                $multimediaObject,
                $request->getScheme(),
                $request->getHost()
            );
        }

        return $this->json([
            'total' => $response->total,
            'rows' => $rows,
        ]);
    }
}
