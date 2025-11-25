<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ViewSeriesEvents\ViewSeriesEventsRequest;
use App\Series\Application\ViewSeriesEvents\ViewSeriesEventsService;
use App\Shared\UI\Backend\Helpers\BooleanIcon;
use App\Shared\UI\Backend\Helpers\DateFormat;
use App\Shared\UI\Backend\Helpers\Thumbnail;
use MongoDB\BSON\ObjectId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class ViewSeriesEventsDataController extends AbstractController
{
    public function __construct(private ViewSeriesEventsService $viewSeriesEventsService) {}

    public function __invoke(
        Request $request,
        RouterInterface $router,
        string $id,
    ): JsonResponse {
        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 10);
        $sort = $request->query->get('sort', 'title');
        $order = $request->query->get('order', 'asc');
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

        $response = ($this->viewSeriesEventsService)($dto);

        $rows = [];
        foreach ($response->multimediaObjects as $item) {
            $actionsHtml = sprintf(
                '<div class="d-flex gap-1 justify-content-end">
                    <a href="%s" class="btn btn-sm"><i class="fa fa-eye"></i></a>
                    <a href="%s" class="btn btn-sm"><i class="fa fa-times"></i></a>
                </div>',
                '#',
                '#'
            );

            $rows[] = [
                'thumbnail' => Thumbnail::convert($item->getMainThumbnail($request->getScheme(), $request->getHost())),
                'title' => $item->getTitle(),
                'actions' => $actionsHtml,
            ];
        }

        return $this->json([
            'total' => $response->total,
            'rows' => $rows,
        ]);
    }
}
