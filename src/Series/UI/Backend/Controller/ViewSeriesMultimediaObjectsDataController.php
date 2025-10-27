<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ViewSeriesMultimediaObjects\ViewSeriesMultimediaObjectsRequest;
use App\Series\Application\ViewSeriesMultimediaObjects\ViewSeriesMultimediaObjectsService;
use MongoDB\BSON\ObjectId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class ViewSeriesMultimediaObjectsDataController extends AbstractController
{
    public function __construct(private ViewSeriesMultimediaObjectsService $viewSeriesMultimediaObjectsService) {}

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

        $dto = new ViewSeriesMultimediaObjectsRequest(
            page: $page,
            limit: $limit,
            filters: $filters,
            sort: $sort,
            order: $order,
        );

        $response = ($this->viewSeriesMultimediaObjectsService)($dto);

        $rows = [];
        foreach ($response->multimediaObjects as $om) {
            $actionsHtml = sprintf(
                '<div class="d-flex gap-1 justify-content-end">
                    <a href="%s" class="btn btn-sm"><i class="fa fa-eye"></i></a>
                    <a href="%s" class="btn btn-sm"><i class="fa fa-copy"></i></a>
                    <a href="%s" class="btn btn-sm"><i class="fa fa-times"></i></a>
                </div>',
                $router->generate('multimediaobject_view', ['id' => $om->getId()]),
                '#',
                '#'
            );

            $rows[] = [
                'thumbnail' => $om->getMainThumbnail($request->getScheme(), $request->getHost()),
                'title' => $om->getTitle(),
                'status' => $om->getStatus(),
                'publicDate' => $om->getPublicDate()->format('Y-m-d H:i'),
                'recordDate' => $om->getRecordDate()->format('Y-m-d H:i'),
                'duration' => $om->getDurationString(),
                'hide' => $om->isHidden(),
                'type' => $om->getType(),
                'actions' => $actionsHtml,
            ];
        }

        return $this->json([
            'total' => $response->total,
            'rows' => $rows,
        ]);
    }
}
