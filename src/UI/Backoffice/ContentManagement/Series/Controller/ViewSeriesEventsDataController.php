<?php

namespace App\UI\Backoffice\ContentManagement\Series\Controller;

use App\ContentManagement\Series\Application\ViewSeriesEvents\ViewSeriesEventsRequest;
use App\ContentManagement\Series\Application\ViewSeriesEvents\ViewSeriesEventsService;
use App\UI\Backoffice\ContentManagement\Series\Presenter\EventDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ViewSeriesEventsDataController extends AbstractController
{
    public function __construct(
        private ViewSeriesEventsService $viewSeriesEventsService,
        private EventDataTablePresenter $presenter
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
            'series_id' => $id,  // Pass as string, let Application layer handle conversion
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
        foreach ($response->multimediaObjects as $event) {
            $rows[] = $this->presenter->present(
                $event,
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
