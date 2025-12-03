<?php

declare(strict_types=1);

namespace App\Streaming\UI\Backoffice\Controller;

use App\Streaming\Application\Channel\List\ListChannelsRequest;
use App\Streaming\Application\Channel\List\ListChannelsService;
use App\Streaming\UI\Backoffice\Presenter\ChannelDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ListChannelsDataController extends AbstractController
{
    public function __construct(
        private readonly ListChannelsService $listChannelsService,
        private readonly ChannelDataTablePresenter $presenter
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 10);
        $sort = $request->query->get('sort', 'name.en');
        $order = $request->query->get('order', 'asc');

        $page = (int) floor($offset / $limit) + 1;

        $dto = new ListChannelsRequest(
            page: $page,
            limit: $limit,
            sort: $sort,
            order: $order
        );

        $channelsResponse = ($this->listChannelsService)($dto);

        $rows = [];
        foreach ($channelsResponse->channels as $channel) {
            $rows[] = $this->presenter->present($channel, $request->getLocale());
        }

        return $this->json([
            'total' => $channelsResponse->total,
            'rows' => $rows,
        ]);
    }
}

