<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ListSeries\ListSeriesHandler;
use App\Series\Application\ListSeries\ListSeriesRequest;
use App\Series\Domain\SeriesRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class ListSeriesDataController extends AbstractController
{
    public function __invoke(
        Request $request,
        SeriesRepositoryInterface $repository,
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
            $filters['title'] = $search;
            $filters['subtitle'] = $search;
        }

        $dto = new ListSeriesRequest(
            page: $page,
            limit: $limit,
            filters: $filters,
            sort: $sort,
            order: $order
        );

        $handler = new ListSeriesHandler($repository);
        $seriesResponse = $handler->execute($dto);

        $rows = [];
        foreach ($seriesResponse->series as $item) {
            $actionsHtml = sprintf(
                '<div class="d-flex gap-1 justify-content-end">
        <a href="%s" class="btn btn-sm"><i class="fa fa-eye"></i></a>
        <a href="%s" class="btn btn-sm"><i class="fa fa-times"></i></a>
     </div>',
                $router->generate('series_view', ['id' => $item->getId()]),
                '#'
            );

            $rows[] = [
                'oneSeries' => $item,
                'thumbnail' => $item->getMainThumbnail($request->getScheme(), $request->getHost()),
                'objectCount' => $repository->countMultimediaObjects($item->getId()),
                'eventCount' => $repository->countEventMultimediaObjects($item->getId()),
                'actions' => $actionsHtml,
            ];
        }

        return $this->json([
            'total' => $seriesResponse->total,
            'rows' => $rows,
        ]);
    }
}
