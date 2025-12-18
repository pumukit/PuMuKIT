<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Infrastructure\Ui\Backoffice\Http\Controller;

use App\ContentManagement\MultimediaObject\Application\Search\SearchMultimediaObjectsByCriteriaRequest;
use App\ContentManagement\MultimediaObject\Application\Search\SearchMultimediaObjectsByCriteriaService;
use App\ContentManagement\MultimediaObject\Infrastructure\Ui\Backoffice\Http\Presenter\GroupMultimediaObjectDataTablePresenter;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class GroupMultimediaObjectsDataController extends AbstractController
{
    public function __construct(
        private readonly SearchMultimediaObjectsByCriteriaService $searchService,
        private readonly GroupMultimediaObjectDataTablePresenter $presenter
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $groupId = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return $this->json(['total' => 0, 'rows' => []]);
        }

        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 10);
        $sort = $request->query->get('sort', 'title');
        $order = $request->query->get('order', 'asc');

        $searchRequest = new SearchMultimediaObjectsByCriteriaRequest(
            filters: [
                ['field' => 'groups', 'operator' => '=', 'value' => $groupId->value()],
            ],
            orderBy: $sort,
            order: $order,
            limit: $limit,
            offset: $offset
        );

        $searchResponse = ($this->searchService)($searchRequest);

        $rows = array_map(
            fn ($mm) => $this->presenter->present($mm, $request->getLocale()),
            $searchResponse['items']
        );

        return $this->json([
            'total' => $searchResponse['total'],
            'rows' => $rows,
        ]);
    }
}
