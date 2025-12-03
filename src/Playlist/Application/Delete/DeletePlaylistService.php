<?php

declare(strict_types=1);

namespace App\Playlist\Application\Delete;

use App\Playlist\Domain\Event\PlaylistDeletedEvent;
use App\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final class DeletePlaylistService
{
    public function __construct(
        private readonly PlaylistRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(DeletePlaylistRequest $request): DeletePlaylistResponse
    {
        $playlist = $this->repository->find($request->id);

        if (null === $playlist) {
            return new DeletePlaylistResponse(
                success: false,
                message: sprintf('Playlist with id "%s" not found.', $request->id)
            );
        }

        $playlistTitle = $playlist->getTitle();

        $this->repository->delete($playlist);
        $this->eventBus->dispatch(new PlaylistDeletedEvent($playlist));

        return new DeletePlaylistResponse(
            success: true,
            message: sprintf('Playlist "%s" deleted successfully.', $playlistTitle)
        );
    }
}
