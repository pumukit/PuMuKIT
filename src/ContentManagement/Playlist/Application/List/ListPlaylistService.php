<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\List;

use App\ContentManagement\Playlist\Domain\Repository\PlaylistRepositoryInterface;

final class ListPlaylistService
{
    public function __construct(private readonly PlaylistRepositoryInterface $repository) {}

    public function __invoke(ListPlaylistRequest $request): ListPlaylistResponse
    {
        $playlists = $this->repository->findByFiltersPaginated(
            $request->filters,
            $request->page,
            $request->limit,
            $request->sort,
            $request->order
        );

        $total = $this->repository->countByFilters($request->filters);

        return new ListPlaylistResponse($playlists, $total);
    }
}
