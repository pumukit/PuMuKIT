<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\List\ListSeriesRequest;
use App\Series\Application\List\ListSeriesService;
use App\Series\UI\Backend\Presenter\SeriesDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ListSeriesDataController extends AbstractController
{
    public function __construct(
        private ListSeriesService $listSeriesService,
        private SeriesDataTablePresenter $presenter
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 10);
        $sort = $request->query->get('sort', 'title');
        $order = $request->query->get('order', 'asc');
        $search = $request->query->get('search', '');

        $page = (int) floor($offset / $limit) + 1;

        $filters = [];
        if ($search) {
            $filters['title.'.$request->getLocale()] = $search;
            $filters['subtitle.'.$request->getLocale()] = $search;
        }

        $dto = new ListSeriesRequest(
            page: $page,
            limit: $limit,
            filters: $filters,
            sort: $sort,
            order: $order
        );

        $seriesResponse = ($this->listSeriesService)($dto);

        $rows = [];
        foreach ($seriesResponse->series as $series) {
            $rows[] = $this->presenter->present(
                $series,
                $request->getScheme(),
                $request->getHost(),
                $request->getLocale()
            );
        }

        return $this->json([
            'total' => $seriesResponse->total,
            'rows' => $rows,
        ]);
    }
}
