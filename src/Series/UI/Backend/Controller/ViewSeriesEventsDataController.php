<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ViewSeriesEvents\ViewSeriesEventsRequest;
use App\Series\Application\ViewSeriesEvents\ViewSeriesEventsHandler;
use App\Series\Domain\SeriesRepositoryInterface;
use MongoDB\BSON\ObjectId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class ViewSeriesEventsDataController extends AbstractController
{
    public function __invoke(
        Request $request,
        SeriesRepositoryInterface $repository,
        RouterInterface $router,
        string $id,
    ): JsonResponse {

        $offset = (int) $request->query->get('offset', 0);
        $limit  = (int) $request->query->get('limit', 10);
        $sort   = $request->query->get('sort', 'title');
        $order  = $request->query->get('order', 'asc');
        $search = $request->query->get('search', '');

        $page = (int) floor($offset / $limit) + 1;

        $filters = [
            'series.id' => new ObjectId($id),
        ];
        if ($search) {
            $filters['title'] = $search;
            $filters['subtitle'] = $search;
        }

        $dto = new ViewSeriesEventsRequest(
            page: $page,
            limit: $limit,
            filters: $filters,
            sort: $sort,
            order: $order,
        );

        $handler = new ViewSeriesEventsHandler($repository);
        $response = $handler->handle($dto);

        $rows = [];
        foreach ($response->multimediaObjects as $om) {
            $actionsHtml = sprintf(
                '<div class="d-flex gap-1 justify-content-end">
                    <a href="%s" class="btn btn-sm"><i class="fa fa-eye"></i></a>
                    <a href="%s" class="btn btn-sm"><i class="fa fa-times"></i></a>
                </div>',
                '#',
                '#'
            );

            $rows[] = [
                'thumbnail' => $om->getMainThumbnail($request->getScheme(), $request->getHost()),
                'title' => $om->getTitle(),
                'actions' => $actionsHtml
            ];
        }

        return $this->json([
            'total' => $response->total,
            'rows' => $rows
        ]);
    }
}
