<?php

namespace App\Series\UI\Backend\Controller;

use App\MultimediaObject\UI\Backend\Helpers\DurationFormat;
use App\MultimediaObject\UI\Backend\Helpers\StatusIcon;
use App\MultimediaObject\UI\Backend\Helpers\TypeIcon;
use App\Series\Application\ViewSeriesMultimediaObjects\ViewSeriesMultimediaObjectsRequest;
use App\Series\Application\ViewSeriesMultimediaObjects\ViewSeriesMultimediaObjectsService;
use App\Shared\UI\Backend\Helpers\BooleanIcon;
use App\Shared\UI\Backend\Helpers\DateFormat;
use App\Shared\UI\Backend\Helpers\TextTruncate;
use App\Shared\UI\Backend\Helpers\Thumbnail;
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
        foreach ($response->multimediaObjects as $item) {
            $actionsHtml = sprintf(
        '<div class="d-flex gap-1 justify-content-end">
                    <a href="%s" class="btn btn-sm"><i class="fa fa-eye"></i></a>
                    <a href="%s" class="btn btn-sm"><i class="fa fa-copy"></i></a>
                    <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this multimedia object?\');">
                        <button type="submit" class="btn btn-sm">
                            <i class="fa fa-trash"></i>
                        </button>
                    </form>
                </div>',
                $router->generate('multimediaobject_view', ['id' => $item->getId()]),
                '#',
                $router->generate('multimediaobject_delete', ['id' => $item->getId()]),
            );

            $rows[] = [
                'thumbnail' => Thumbnail::convert($item->getMainThumbnail($request->getScheme(), $request->getHost())),
                'title' => TextTruncate::long($item->getTitle()),
                'status' => StatusIcon::convert($item->getStatus()),
                'public_date' => DateFormat::format($item->getPublicDate()),
                'record_date' => DateFormat::format($item->getRecordDate()),
                'duration' => '---',
                'hide' => BooleanIcon::convert($item->  isHidden()),
                'type' => TypeIcon::convert($item->getType()),
                'actions' => $actionsHtml,
            ];
        }

        return $this->json([
            'total' => $response->total,
            'rows' => $rows,
        ]);
    }
}
