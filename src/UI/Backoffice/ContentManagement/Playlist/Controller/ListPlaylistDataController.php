<?php

namespace App\UI\Backoffice\ContentManagement\Playlist\Controller;

use App\ContentManagement\Playlist\Application\List\ListPlaylistRequest;
use App\ContentManagement\Playlist\Application\List\ListPlaylistService;
use App\UI\Backoffice\ContentManagement\Playlist\Presenter\PlaylistDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ListPlaylistDataController extends AbstractController
{
    public function __construct(
        private ListPlaylistService $listPlaylistService,
        private PlaylistDataTablePresenter $presenter
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', '1');
        $limit = (int) $request->query->get('limit', '10');
        $sort = $request->query->get('sort', 'title');
        $order = $request->query->get('order', 'asc');
        $search = $request->query->get('search', '');


        $filters = [];
        if ($search) {
            $filters['title.'.$request->getLocale()] = $search;
            $filters['subtitle.'.$request->getLocale()] = $search;
        }

        $dto = new ListPlaylistRequest(
            page: $page,
            limit: $limit,
            filters: $filters,
            sort: $sort,
            order: $order
        );

        $playlistResponse = ($this->listPlaylistService)($dto);

        $rows = [];
        foreach ($playlistResponse->playlists as $playlist) {
            $rows[] = $this->presenter->present($playlist);
        }

        return $this->json([
            'total' => $playlistResponse->total,
            'rows' => $rows,
        ]);
    }
}
