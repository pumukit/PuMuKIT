<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\List\ListSeriesService;
use App\Series\Application\List\ListSeriesRequest;
use App\Series\Domain\SeriesRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class ListSeriesDataController extends AbstractController
{
    public function __construct(
        private ListSeriesService $listSeriesService,
        private SeriesRepositoryInterface $seriesRepository
    ) {}

    public function __invoke(
        Request $request,
        RouterInterface $router
    ): JsonResponse {
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
        foreach ($seriesResponse->series as $item) {
            $actionsHtml = sprintf(
                '<div class="d-flex gap-1 justify-content-end">
        <a href="%s" class="btn btn-sm"><i class="fa fa-eye"></i></a>
        <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to clone this series?\');">
            <button type="submit" class="btn btn-sm">
                <i class="fa fa-copy"></i>
            </button>
        </form>
        <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this series?\');">
            <button type="submit" class="btn btn-sm">
                <i class="fa fa-trash"></i>
            </button>
        </form>
    </div>',
                $router->generate('series_view', ['id' => $item->getId()]),
                $router->generate('series_clone', ['id' => $item->getId()]),
                $router->generate('series_delete', ['id' => $item->getId()])
            );


            $rows[] = [
                'oneSeries' => $item,
                'thumbnail' => $item->getMainThumbnail($request->getScheme(), $request->getHost()),
                'objectCount' => $this->seriesRepository->countMultimediaObjects($item->getId()),
                'eventCount' => $this->seriesRepository->countEventMultimediaObjects($item->getId()),
                'actions' => $actionsHtml,
            ];
        }

        return $this->json([
            'total' => $seriesResponse->total,
            'rows' => $rows,
        ]);
    }
}
